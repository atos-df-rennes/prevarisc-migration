<?php

namespace App\Service;

use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Dossier as DossierEntity;
use App\Entity\Etablissement as EtablissementEntity;
use App\Entity\Utilisateur;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;

final class Dossier
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly DossierRepository $dossierRepository, private readonly Security $security, private readonly PlatauTempsRestant $servicePlatauTempsRestant, private readonly TexteApplicable $texteApplicableService)
    {
    }

    /**
     * Rétablit un dossier supprimé en remettant à null les champs de suppression.
     */
    public function restore(DossierEntity $dossier): void
    {
        $dossier->setDateSuppression(null);
        $dossier->setDeletedBy(null);

        $this->entityManager->flush();
    }

    /**
     * Effectue la suppression logique de tous les dossiers non encore supprimés
     * associés à un établissement (RG-ETB-019).
     *
     * Utilise des requêtes SQL directes pour éviter de charger toutes les entités en mémoire.
     *
     * Iso-legacy (Service_Dossier::deleteByEtab + delete($id, $date, $uniqueEtab=true)) :
     * - Seuls les dossiers liés EXCLUSIVEMENT à cet établissement sont supprimés.
     *   Un dossier partagé avec d'autres établissements non supprimés est préservé.
     * - Les affectations commission (dossieraffectation) sont supprimées pour chaque
     *   dossier effectivement supprimé.
     */
    public function deleteByEtablissement(EtablissementEntity $etablissement, Utilisateur $user): void
    {
        $date = (new \DateTime())->format('Y-m-d');
        $conn = $this->entityManager->getConnection();
        $etabId = $etablissement->getId();
        $userId = $user->getId();

        // Condition iso-legacy $uniqueEtab : le dossier n'est lié qu'à au plus
        // 1 établissement non supprimé (l'établissement courant en cours de suppression).
        $exclusif = '(
            SELECT COUNT(*)
            FROM etablissementdossier ed2
            INNER JOIN etablissement e2 ON e2.ID_ETABLISSEMENT = ed2.ID_ETABLISSEMENT
            WHERE ed2.ID_DOSSIER = d.ID_DOSSIER
              AND e2.DATESUPPRESSION_ETABLISSEMENT IS NULL
        ) <= 1';

        // Suppression des affectations commission des dossiers exclusifs
        $conn->executeStatement(
            'DELETE da
             FROM dossieraffectation da
             INNER JOIN dossier d ON d.ID_DOSSIER = da.ID_DOSSIER_AFFECT
             INNER JOIN etablissementdossier ed ON ed.ID_DOSSIER = d.ID_DOSSIER
             WHERE ed.ID_ETABLISSEMENT = :etabId
               AND d.DATESUPPRESSION_DOSSIER IS NULL
               AND '.$exclusif,
            ['etabId' => $etabId]
        );

        // Suppression logique des dossiers exclusivement liés à cet établissement
        $conn->executeStatement(
            'UPDATE dossier d
             INNER JOIN etablissementdossier ed ON ed.ID_DOSSIER = d.ID_DOSSIER
             SET d.DATESUPPRESSION_DOSSIER = :date, d.DELETED_BY = :userId
             WHERE ed.ID_ETABLISSEMENT = :etabId
               AND d.DATESUPPRESSION_DOSSIER IS NULL
               AND '.$exclusif,
            [
                'date' => $date,
                'userId' => $userId,
                'etabId' => $etabId,
            ]
        );
    }

    public function nombreNouvellesPiecesJointes(DossierEntity $dossier): int
    {
        $user = $this->security->getUser();

        if (!$user instanceof Utilisateur) {
            return 0;
        }

        $dateDerniereVisitePage = $user->getDerniereVisitePiecesDossier();

        if (null === $dateDerniereVisitePage) {
            return 0;
        }

        return $this->dossierRepository->findNombreNouvellesPieces($dossier, $dateDerniereVisitePage);
    }

    // @todo: A refactoriser
    /**
     * Extrait les données utiles pour l'affichage des dossiers.
     *
     * @param array<DossierEntity> $dossiers Tableau de dossiers (chaque élément est un tableau associatif)
     *
     * @return array<int, array<string, mixed>> Tableau des données utiles pour chaque dossier
     */
    public function extraireDonneesUtiles(array $dossiers): array
    {
        if ([] === $dossiers) {
            return [];
        }

        // Collecte de tous les IDs une seule fois
        $allIds = [];
        foreach ($dossiers as $dossier) {
            $id = $dossier->getId();
            if (null !== $id) {
                $allIds[] = $id;
            }
        }

        // Pré-calcul des IDs avec enfants (dl déjà chargé via addSelect dans le repository)
        $idsAvecEnfants = [];
        foreach ($dossiers as $dossier) {
            if (count($dossier->getDossiersLies()) > 0) {
                $id = $dossier->getId();
                if (null !== $id) {
                    $idsAvecEnfants[] = $id;
                }
            }
        }

        // Bulk : alertes, PJ count et nouvelles PJ — 4 requêtes max hors boucle
        $alertes = $this->dossierRepository->hasAlerteReceptionTravauxBulk($idsAvecEnfants);
        $echeanciers = $this->dossierRepository->hasEcheancierTravauxBulk($allIds);
        $nbPJParDossier = $this->dossierRepository->countPiecesJointesBulk($allIds);

        $donneesUtiles = [];
        foreach ($dossiers as $dossierEntity) {
            $donneeUtile = [];
            $donneeUtile['nbDossiersLies'] = count($dossierEntity->getDossiersLies());
            $id = $dossierEntity->getId();
            $donneeUtile['alerteReceptionTravaux'] = $alertes[$id] ?? false;
            $donneeUtile['echeancierTravaux'] = $echeanciers[$id] ?? false;

            $donneeUtile['verrou'] = $dossierEntity->hasVerrou();
            $donneeUtile['id'] = $id;
            $donneeUtile['nbPJ'] = $nbPJParDossier[$id] ?? 0;
            $donneeUtile['objet'] = $dossierEntity->getObjet();
            $donneeUtile['incomplet'] = $dossierEntity->isIncomplet();
            $donneeUtile['dateReception'] = $dossierEntity->getDateReception();
            $donneeUtile['dateReponse'] = $dossierEntity->getDateReponse();
            $donneeUtile['avisDiffere'] = $dossierEntity->hasAvisDiffere();
            $donneeUtile['avisId'] = $dossierEntity->getAvisCommission() ? $dossierEntity->getAvisCommission()->getId() : null;
            $donneeUtile['donneAvis'] = $dossierEntity->donneAvis();
            $donneeUtile['dateReceptionFormatee'] = null !== $dossierEntity->getDateReception()
                ? $this->formatDateDiff($dossierEntity->getDateReception())
                : null;

            if (null !== $donneeUtile['objet']) {
                if (strlen($donneeUtile['objet']) > 70) {
                    $donneeUtile['objet'] = substr($donneeUtile['objet'], 0, 70).'(...)';
                }

                if (!$dossierEntity->getDocumentsUrbanisme()->isEmpty()) {
                    $donneeUtile['objet'] .= ' - '.$dossierEntity->getNumerosUrbanisme();
                }
            } elseif (!$dossierEntity->getDocumentsUrbanisme()->isEmpty()) {
                $donneeUtile['objet'] = $dossierEntity->getNumerosUrbanisme();
            } else {
                $donneeUtile['objet'] = '';
            }

            $libelle = '';
            $type = $dossierEntity->getType();
            if (null !== $type) {
                if (null !== $type->getLibelle()) {
                    $libelle .= $type->getLibelle();
                }

                $donneeUtile['typeId'] = $type->getId();
            } else {
                $donneeUtile['typeId'] = null;
            }

            $nature = null !== $dossierEntity->getNature() ? $dossierEntity->getNature()->getLibelle() : null;
            if (null !== $nature) {
                if ('' !== $libelle) {
                    $libelle .= ' - ';
                }

                $libelle .= $nature;
            }

            if ('' !== $donneeUtile['objet']) {
                if ('' !== $libelle) {
                    $libelle .= ' - ';
                }

                $libelle .= $donneeUtile['objet'];
            }

            $donneeUtile['objet'] = $libelle;
            if ($dossierEntity->getEtablissements()->first()) {
                $donneeUtile['etablissement'] = $dossierEntity->getEtablissements()->first()->getLatestInformations()->getLibelle();
            } else {
                $donneeUtile['etablissement'] = null;
            }

            if (null !== $dossierEntity->getDateCommission()) {
                $donneeUtile['date'] = $dossierEntity->getDateCommission();
                $donneeUtile['libelleDate'] = 'ayant pour date de passage en commission le';
            } elseif (null !== $dossierEntity->getDateVisite()) {
                $donneeUtile['date'] = $dossierEntity->getDateVisite();
                $donneeUtile['libelleDate'] = 'ayant pour date de visite le';
            } else {
                $donneeUtile['date'] = $dossierEntity->getDateInsert();
                $donneeUtile['libelleDate'] = 'créé le';
            }

            $consultationPlatau = $dossierEntity->getConsultationPlatau();

            if (null !== $consultationPlatau) {
                $donneeUtile['consultationPlatauId'] = $consultationPlatau->getIdentifiant();

                if (null !== $consultationPlatau->getDateReponseAttendue()) {
                    $donneeUtile['tempsRestant'] = $this->servicePlatauTempsRestant->calculate($consultationPlatau->getDateReponseAttendue());
                    $donneeUtile['couleur'] = $this->servicePlatauTempsRestant->getCouleurTempsRestant($consultationPlatau->getDateReponseAttendue());
                } else {
                    $donneeUtile['tempsRestant'] = null;
                    $donneeUtile['couleur'] = null;
                }
            } else {
                $donneeUtile['consultationPlatauId'] = null;
                $donneeUtile['tempsRestant'] = null;
                $donneeUtile['couleur'] = null;
            }

            $donneesUtiles[] = $donneeUtile;
        }

        return $donneesUtiles;
    }

    /**
     * Récupère les textes applicables d'un dossier et les trie par type.
     *
     * @return array{}|array{int, array{'titre': ?string, 'textes': array{int, ?string}}}
     */
    public function getTextesApplicables(int $idDossier): array
    {
        $textesApplicablesData = $this->dossierRepository->getTextesApplicablesByType($idDossier);
        if (null === $textesApplicablesData) {
            return [];
        }

        $textesApplicablesValues = $textesApplicablesData->getTextesApplicables()->getValues();

        return $this->texteApplicableService->trierParType($textesApplicablesValues);
    }

    /**
     * Formate la différence entre une date et maintenant (ex: "2 jours", "1 heure").
     *
     * Reproduit le comportement du helper Zend View_Helper_Dates::formatDateDiff().
     */
    private function formatDateDiff(\DateTimeInterface $start): string
    {
        $end = new \DateTime();
        $interval = $end->diff($start);

        $doPlural = (fn (int $nb, string $str): string => $nb > 1 ? $str.'s' : $str);

        $format = [];
        if (0 !== $interval->y) {
            $format[] = '%y '.$doPlural($interval->y, 'année');
        }

        if (0 !== $interval->m) {
            $format[] = '%m mois';
        }

        if (0 !== $interval->d) {
            $format[] = '%d '.$doPlural($interval->d, 'jour');
        }

        if (0 !== $interval->h) {
            $format[] = '%h '.$doPlural($interval->h, 'heure');
        }

        if (0 !== $interval->i) {
            $format[] = '%i '.$doPlural($interval->i, 'minute');
        }

        if (0 !== $interval->s) {
            if ([] === $format) {
                return '<= 1 min';
            }

            $format[] = '%s '.$doPlural($interval->s, 'seconde');
        }

        $pattern = array_shift($format);

        if (null === $pattern) {
            return '<= 1 min';
        }

        return $interval->format($pattern);
    }
}
