<?php

namespace App\Service;

use App\Dto\FusionDesCommunes\Commune;
use App\Dto\FusionDesCommunes\FusionDeCommunes;
use App\Dto\FusionDesCommunes\ResultatFusion;
use App\Exception\FusionFunctionalException;
use App\Exception\FusionTechnicalException;
use App\Repository\AdresseRueRepository;
use App\Repository\CommissionRegleRepository;
use App\Repository\CommuneRepository;
use App\Repository\EtablissementAdresseRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class FusionDesCommunes
{
    public function __construct(private SerializerInterface $serializer, private LoggerInterface $logger, private EntityManagerInterface $entityManager, private CommuneRepository $communeRepository, private EtablissementAdresseRepository $etablissementAdresseRepository, private AdresseRueRepository $adresseRueRepository, private UtilisateurRepository $utilisateurRepository, private CommissionRegleRepository $commissionRegleRepository)
    {
    }

    /**
     * Traite un fichier de fusion de communes.
     *
     * Une erreur technique (fichier illisible, JSON invalide) interrompt tout le
     * traitement via une {@see FusionTechnicalException}. À l'inverse, l'échec
     * fonctionnel d'un lot (commune introuvable, libellé incohérent, informations
     * manquantes…) n'interrompt pas le traitement : le lot est ignoré et signalé
     * dans le {@see ResultatFusion} retourné, les lots suivants continuent.
     *
     * @param false|string $fileContent
     *
     * @throws FusionTechnicalException
     */
    public function fusionner($fileContent): ResultatFusion
    {
        if (false === $fileContent) {
            throw new FusionTechnicalException('Le contenu du fichier est incorrect.');
        }

        try {
            /** @var FusionDeCommunes[] $listeFusionCommunes */
            $listeFusionCommunes = $this->serializer->deserialize($fileContent, FusionDeCommunes::class.'[]', 'json');
        } catch (\Exception $exception) {
            throw new FusionTechnicalException('Le fichier de fusion est invalide et n\'a pas pu être analysé.', 0, $exception);
        }

        $resultat = new ResultatFusion();

        foreach ($listeFusionCommunes as $fusionCommune) {
            try {
                $this->verifierCommune($fusionCommune);

                $this->updateAdressesEtablissements($fusionCommune);
                $this->updateRues($fusionCommune);

                $this->deleteCommunes($fusionCommune->listeCommunes());

                $resultat->ajouterSucces($fusionCommune->nom());
            } catch (FusionFunctionalException $exception) {
                $this->logger->error($exception->getMessage());
                $resultat->ajouterEchec($fusionCommune->nom(), $exception->getMessage());
            }
        }

        return $resultat;
    }

    /**
     * Vérifie que la commune cible d'une fusion existe et est cohérente.
     *
     * @throws FusionFunctionalException si la commune est introuvable ou incohérente
     */
    private function verifierCommune(FusionDeCommunes $fusionCommune): void
    {
        $commune = $this->communeRepository->find($fusionCommune->numinsee());

        if (null === $commune) {
            throw new FusionFunctionalException(\sprintf("La commune ayant pour code INSEE %s n'a pas été trouvée dans la base de données.", $fusionCommune->numinsee()));
        }

        if (null === $commune->getLibelle()) {
            throw new FusionFunctionalException(\sprintf("La commune d'identifiant %s a été trouvée mais ne comporte pas de libellé.", $fusionCommune->numinsee()));
        }

        if (0 !== strcmp($fusionCommune->nom(), $commune->getLibelle())) {
            throw new FusionFunctionalException(\sprintf("La commune ayant pour code INSEE %s n'a pas pour libellé %s dans la base de données. Libellé en base de données : %s", $fusionCommune->numinsee(), $fusionCommune->nom(), $commune->getLibelle()));
        }
    }

    private function updateAdressesEtablissements(FusionDeCommunes $fusionCommune): void
    {
        foreach ($fusionCommune->listeCommunes() as $commune) {
            $newCommune = $this->communeRepository->find($fusionCommune->numinsee());

            if (null === $newCommune) {
                throw new FusionFunctionalException(\sprintf("Impossible de fusionner dans la commune %s : elle n'existe pas en base de données.", $fusionCommune->numinsee()));
            }

            $addressesToUpdate = $this->etablissementAdresseRepository->findBy(['commune' => $commune->numinsee()]);

            foreach ($addressesToUpdate as $addressToUpdate) {
                $addressToUpdate->setCommune($newCommune);
                $this->entityManager->persist($addressToUpdate);
            }

            $this->entityManager->flush();
        }
    }

    private function updateRues(FusionDeCommunes $fusionCommune): void
    {
        foreach ($fusionCommune->listeCommunes() as $commune) {
            try {
                $newCommune = $this->communeRepository->find($fusionCommune->numinsee());

                if (null === $newCommune) {
                    throw new \Exception(sprintf("La nouvelle commune %s n'existe pas en base de données.", $fusionCommune->numinsee()));
                }

                $ruesToUpdate = $this->adresseRueRepository->findBy(['commune' => $commune->numinsee()]);

                foreach ($ruesToUpdate as $rueToUpdate) {
                    $rueToUpdate->setCommune($newCommune);
                    $this->entityManager->persist($rueToUpdate);
                }

                $this->entityManager->flush();
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }

    /**
     * Supprime les liaisons groupement-commune associées à la commune absorbée.
     *
     * La commune est le côté inverse de la relation ManyToMany ; le retrait se fait
     * donc via chaque groupement (côté propriétaire), ce qui provoque la suppression
     * automatique des lignes correspondantes dans la table de jointure groupementcommune.
     */
    private function deleteGroupementCommuneLiaisons(\App\Entity\Commune $commune): void
    {
        foreach ($commune->getGroupements() as $groupement) {
            $groupement->removeCommune($commune);
            $this->entityManager->persist($groupement);
        }
    }

    /**
     * Supprime les règles de commission associées à la commune absorbée.
     *
     * Contrairement à la contrainte de clé étrangère NUMINSEE_COMMUNE (ON DELETE CASCADE),
     * les tables de jointure liées (commissionreglecategorie, commissionregleclasse,
     * commissionregletype) n'ont pas de contrainte de clé étrangère en base : une
     * suppression au niveau SQL laisserait donc des lignes orphelines. On passe donc
     * explicitement par Doctrine pour que les collections ManyToMany et les entités
     * liées (étude/visite, local sommeil) soient nettoyées correctement.
     */
    private function deleteCommissionRegles(\App\Entity\Commune $commune): void
    {
        $regles = $this->commissionRegleRepository->findBy(['commune' => $commune]);

        foreach ($regles as $regle) {
            $this->entityManager->remove($regle);
        }
    }

    /**
     * @param Commune[] $communes
     */
    private function deleteCommunes(array $communes): void
    {
        foreach ($communes as $commune) {
            try {
                $communeEntity = $this->communeRepository->find($commune->numinsee());

                if (null === $communeEntity) {
                    throw new FusionFunctionalException(sprintf("La commune %s n'existe pas en base de données.", $commune->numinsee()));
                }

                $this->deleteGroupementCommuneLiaisons($communeEntity);
                $this->deleteCommissionRegles($communeEntity);

                $utilisateur = $this->utilisateurRepository->findOneBy(['informations' => $communeEntity->getUtilisateurInformations()]);
                $utilisateurInformations = $communeEntity->getUtilisateurInformations();

                if (null === $utilisateur || null === $utilisateurInformations) {
                    if (null === $utilisateur) {
                        $this->logger->error(\sprintf('Aucun utilisateur associé à la commune %s (%s).', $communeEntity->getLibelle(), $communeEntity->getId()));
                    }

                    if (null === $utilisateurInformations) {
                        $this->logger->error(\sprintf('Aucune information utilisateur associée à la commune %s (%s).', $communeEntity->getLibelle(), $communeEntity->getId()));
                    }

                    throw new FusionFunctionalException("Des éléments d'informations sont manquants. Veuillez consulter les logs.");
                }

                $this->entityManager->remove($utilisateur);
                $this->entityManager->remove($communeEntity);
                $this->entityManager->remove($utilisateurInformations);

                $this->entityManager->flush();
            } catch (\Exception $e) {
                $this->logger->error(\sprintf('Erreur lors de la suppression de la commune %s (%s) : %s', $commune->nom(), $commune->numinsee(), $e->getMessage()));

                throw new FusionFunctionalException(\sprintf('Erreur lors de la suppression de la commune %s (%s)', $commune->nom(), $commune->numinsee()), $e->getCode(), $e);
            }
        }
    }

    /**
     * Compose les messages flash à partir du résultat de fusion :
     * succès global, succès partiel avec avertissements par lot KO, ou
     * information si aucune fusion n'était à traiter.
     *
     * @return list<array{type: string, message: string}>
     */
    public function composerFlashsResultat(ResultatFusion $resultat): array
    {
        $flashs = [];

        foreach ($resultat->echecs() as $echec) {
            $flashs[] = [
                'type' => 'warning',
                'message' => \sprintf(
                    "La commune %s n'a pas pu être fusionnée : %s",
                    htmlspecialchars($echec['nom'], \ENT_QUOTES),
                    htmlspecialchars($echec['message'], \ENT_QUOTES)
                ),
            ];
        }

        if ($resultat->hasSuccess()) {
            $flashs[] = [
                'type' => 'success',
                'message' => $resultat->hasFailures()
                    ? 'Certaines communes ont bien été fusionnées. Consultez les avertissements pour les communes non traitées.'
                    : 'Les communes ont bien été fusionnées.',
            ];

            return $flashs;
        }

        if (!$resultat->hasFailures()) {
            $flashs[] = [
                'type' => 'info',
                'message' => 'Aucune commune à fusionner.',
            ];
        }

        return $flashs;
    }
}
