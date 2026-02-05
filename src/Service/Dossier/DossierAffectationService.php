<?php

namespace App\Service\Dossier;

use App\Constant\CommissionTypeEvenement as TypeEvenement;
use App\Entity\Dossier;
use App\Entity\DossierAffectation;
use App\Repository\DateCommissionRepository;
use App\Repository\DossierAffectationRepository;
use App\Service\DossierFields;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion des affectations de dossiers aux commissions/visites.
 */
class DossierAffectationService
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DossierAffectationRepository */
    private $affectationRepository;

    /** @var DateCommissionRepository */
    private $dateCommissionRepository;

    /** @var DossierFields */
    private $dossierFieldsService;

    public function __construct(
        EntityManagerInterface $entityManager,
        DossierAffectationRepository $affectationRepository,
        DateCommissionRepository $dateCommissionRepository,
        DossierFields $dossierFieldsService
    ) {
        $this->entityManager = $entityManager;
        $this->affectationRepository = $affectationRepository;
        $this->dateCommissionRepository = $dateCommissionRepository;
        $this->dossierFieldsService = $dossierFieldsService;
    }

    /**
     * Gère les affectations d'un dossier aux dates de commission/visite.
     *
     * Synchronise les affectations et dénormalise les dates dans les colonnes
     * DATECOMM_DOSSIER et DATEVISITE_DOSSIER pour performance d'affichage.
     *
     * @param Dossier  $dossier                Dossier à affecter
     * @param int|null $idDateCommissionSalle  ID date commission en salle (ou null si désaffecté)
     * @param int|null $idDateCommissionVisite ID date visite/groupe (ou null si désaffecté)
     */
    public function gererAffectations(
        Dossier $dossier,
        ?int $idDateCommissionSalle,
        ?int $idDateCommissionVisite
    ): void {
        // Vérifier si la nature du dossier nécessite des affectations
        $nature = $dossier->getNature();
        $natureId = $nature ? $nature->getId() : null;

        if (null === $nature || null === $natureId) {
            // Pas de nature → Supprimer toutes affectations
            $this->affectationRepository->deleteAllAffectations($dossier);
            $dossier->setDateCommission(null);
            $dossier->setDateVisite(null);

            return;
        }

        $champsNature = $this->dossierFieldsService->getFieldsForNature($natureId);
        if (!in_array('commission', $champsNature, true)) {
            // Nature ne nécessite pas de commission → Supprimer toutes affectations
            $this->affectationRepository->deleteAllAffectations($dossier);
            $dossier->setDateCommission(null);
            $dossier->setDateVisite(null);

            return;
        }

        // Gérer affectation commission en salle (type 1)
        $this->gererAffectation(
            $dossier,
            $idDateCommissionSalle,
            TypeEvenement::TYPE_COMMISSION_SALLE,
            'dateCommission'
        );

        // Gérer affectation visite/groupe (type 2 ou 3)
        $this->gererAffectation(
            $dossier,
            $idDateCommissionVisite,
            [TypeEvenement::TYPE_VISITE, TypeEvenement::TYPE_GROUPE_VISITE],
            'dateVisite'
        );
    }

    /**
     * Gère une affectation spécifique (commission ou visite).
     *
     * @param Dossier   $dossier          Dossier concerné
     * @param int|null  $idDateCommission ID date commission (null = désaffectation)
     * @param int|int[] $typesEvenement   Type(s) événement (1=salle, [2,3]=visite/groupe)
     * @param string    $colonneDate      Nom propriété dossier ('dateCommission' ou 'dateVisite')
     */
    private function gererAffectation(
        Dossier $dossier,
        ?int $idDateCommission,
        $typesEvenement,
        string $colonneDate
    ): void {
        // Récupérer affectation existante pour ce type
        $affectationExistante = $this->affectationRepository->findAffectationByDossierAndType(
            $dossier,
            $typesEvenement
        );

        if ($idDateCommission) {
            // Cas : Affectation demandée
            $dateCommission = $this->dateCommissionRepository->find($idDateCommission);
            if (!$dateCommission) {
                throw new \InvalidArgumentException(sprintf('DateCommission avec ID %d introuvable', $idDateCommission));
            }

            if ($affectationExistante) {
                // Affectation existe déjà → Vérifier si changée
                $affectationDateCommission = $affectationExistante->getDateCommission();

                if ($affectationDateCommission && $affectationDateCommission->getId() !== $idDateCommission) {
                    // Date changée → UPDATE avec reset heures/numéro
                    $affectationExistante->setDateCommission($dateCommission);
                    $affectationExistante->setHeureDebut(null);
                    $affectationExistante->setHeureFin(null);
                    $affectationExistante->setNumero(0);
                }
            } else {
                // Affectation n'existe pas → INSERT
                $nouvelleAffectation = new DossierAffectation();
                $nouvelleAffectation->setDossier($dossier);
                $nouvelleAffectation->setDateCommission($dateCommission);
                $nouvelleAffectation->setNumero(0);
                $this->entityManager->persist($nouvelleAffectation);
            }

            // Dénormalisation : Copier DATE_COMMISSION dans colonne dossier
            $setter = 'set'.ucfirst($colonneDate);
            $dossier->$setter($dateCommission->getDate());
        } else {
            // Cas : Désaffectation (null)
            $setter = 'set'.ucfirst($colonneDate);
            $dossier->$setter(null);

            if ($affectationExistante) {
                // Supprimer affectation existante
                $this->entityManager->remove($affectationExistante);
            }
        }
    }
}
