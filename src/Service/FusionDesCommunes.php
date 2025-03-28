<?php

namespace App\Service;

use App\Dto\FusionDesCommunes\Commune;
use App\Dto\FusionDesCommunes\FusionDeCommunes;
use App\Repository\AdresseRueRepository;
use App\Repository\CommuneRepository;
use App\Repository\EtablissementAdresseRepository;
use App\Repository\UtilisateurInformationsRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class FusionDesCommunes
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CommuneRepository */
    private $communeRepository;

    /** @var EtablissementAdresseRepository */
    private $etablissementAdresseRepository;

    /** @var AdresseRueRepository */
    private $adresseRueRepository;

    /** @var UtilisateurRepository */
    private $utilisateurRepository;

    /** @var UtilisateurInformationsRepository */
    private $utilisateurInformationsRepository;

    public function __construct(
        SerializerInterface $serializer,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        CommuneRepository $communeRepository,
        EtablissementAdresseRepository $etablissementAdresseRepository,
        AdresseRueRepository $adresseRueRepository,
        UtilisateurRepository $utilisateurRepository,
        UtilisateurInformationsRepository $utilisateurInformationsRepository
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->communeRepository = $communeRepository;
        $this->etablissementAdresseRepository = $etablissementAdresseRepository;
        $this->adresseRueRepository = $adresseRueRepository;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->utilisateurInformationsRepository = $utilisateurInformationsRepository;
    }

    /**
     * @param false|string $fileContent
     *
     * @throws \Exception
     */
    public function fusionner($fileContent): void
    {
        if (false === $fileContent) {
            throw new \Exception('Le contenu du fichier est incorrect.');
        }

        /** @var FusionDeCommunes[] $listeFusionCommunes */
        $listeFusionCommunes = $this->serializer->deserialize($fileContent, FusionDeCommunes::class.'[]', 'json');

        foreach ($listeFusionCommunes as $fusionCommune) {
            if (!$this->communeExists($fusionCommune)) {
                continue;
            }

            $this->updateAdressesEtablissements($fusionCommune);
            $this->updateRues($fusionCommune);

            $this->deleteCommunes($fusionCommune->listeCommunes());
        }
    }

    private function communeExists(FusionDeCommunes $fusionCommune): bool
    {
        $commune = $this->communeRepository->find($fusionCommune->numinsee());

        if (null === $commune) {
            $this->logger->error(\sprintf("La commune ayant pour code INSEE %s n'a pas été trouvée dans la base de données.", $fusionCommune->numinsee()));

            return false;
        }

        if (null === $commune->getLibelle()) {
            $this->logger->error(\sprintf("La commune d'identifiant %s a été trouvée mais ne comporte pas de libellé.", $fusionCommune->numinsee()));

            return false;
        }

        if (0 !== strcmp($fusionCommune->nom(), $commune->getLibelle())) {
            $this->logger->error(\sprintf("La commune ayant pour code INSEE %s n'a pas pour libellé %s dans la base de données. Libellé en base de données : %s", $fusionCommune->numinsee(), $fusionCommune->nom(), $commune->getLibelle()));

            return false;
        }

        return true;
    }

    private function updateAdressesEtablissements(FusionDeCommunes $fusionCommune): void
    {
        foreach ($fusionCommune->listeCommunes() as $commune) {
            $newCommune = $this->communeRepository->find($fusionCommune->numinsee());

            if (null === $newCommune) {
                $this->logger->error(\sprintf("La commune d'identifiant %s n'existe pas en base de données.", $fusionCommune->numinsee()));

                throw new \Exception('Impossible de fusionner dans la nouvelle commune. Veuillez consulter les logs.');
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
            $newCommune = $this->communeRepository->find($fusionCommune->numinsee());
            $ruesToUpdate = $this->adresseRueRepository->findBy(['commune' => $commune->numinsee()]);

            foreach ($ruesToUpdate as $rueToUpdate) {
                $rueToUpdate->setCommune($newCommune);
                $this->entityManager->persist($rueToUpdate);
            }

            $this->entityManager->flush();
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
                    throw new \Exception("La commune n'existe pas en base de données.");
                }

                $utilisateur = $this->utilisateurRepository->findOneBy(['informations' => $communeEntity->getUtilisateurInformations()]);
                $utilisateurInformations = $communeEntity->getUtilisateurInformations();

                if (null === $utilisateur || null === $utilisateurInformations) {
                    if (null === $utilisateur) {
                        $this->logger->error(\sprintf('Aucun utilisateur associé à la commune %s (%s).', $communeEntity->getLibelle(), $communeEntity->getId()));
                    }

                    if (null === $utilisateurInformations) {
                        $this->logger->error(\sprintf('Aucune information utilisateur associée à la commune %s (%s).', $communeEntity->getLibelle(), $communeEntity->getId()));
                    }

                    throw new \Exception("Des éléments d'informations sont manquants. Veuillez consulter les logs.");
                }

                $this->utilisateurRepository->remove($utilisateur);
                $this->communeRepository->remove($communeEntity);
                $this->utilisateurInformationsRepository->remove($utilisateurInformations);
            } catch (\Exception $e) {
                $this->logger->error(\sprintf('Erreur lors de la suppression de la commune %s (%s) : %s', $commune->nom(), $commune->numinsee(), $e->getMessage()));

                throw new \Exception(\sprintf('Erreur lors de la suppression de la commune %s (%s)', $commune->nom(), $commune->numinsee()), $e->getCode(), $e);
            }
        }
    }
}
