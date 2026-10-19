<?php

namespace App\Service\Dossier;

use App\Constant\CommissionTypeEvenement as TypeEvenement;
use App\Entity\DateCommission;
use App\Entity\Dossier;
use App\Entity\DossierAffectation;
use App\Entity\DossierNatureListe;
use App\Repository\DateCommissionRepository;
use App\Repository\DossierAffectationRepository;
use App\Service\DossierFields;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion des affectations de dossiers aux commissions/visites.
 */
final class DossierAffectationService
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly DossierAffectationRepository $affectationRepository, private readonly DateCommissionRepository $dateCommissionRepository, private readonly DossierFields $dossierFieldsService)
    {
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
        $natureId = $nature instanceof DossierNatureListe ? $nature->getId() : null;

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
        int|array $typesEvenement,
        string $colonneDate
    ): void {
        // Récupérer affectation existante pour ce type
        $affectationExistante = $this->affectationRepository->findAffectationByDossierAndType(
            $dossier,
            $typesEvenement
        );

        // Cas 1 : Désaffectation (paramètre null) → Early return
        if (!$idDateCommission) {
            $setter = 'set'.ucfirst($colonneDate);
            $dossier->$setter(null);

            if (null !== $affectationExistante) {
                $this->entityManager->remove($affectationExistante);
            }

            return;
        }

        // Cas 2 : Affectation demandée (à partir d'ici, $idDateCommission existe)
        $dateCommission = $this->dateCommissionRepository->find($idDateCommission);
        if (!$dateCommission) {
            throw new \InvalidArgumentException(sprintf('DateCommission avec ID %d introuvable', $idDateCommission));
        }

        // Cas 2.1 : Nouvelle affectation → INSERT et early return
        if (null === $affectationExistante) {
            $nouvelleAffectation = new DossierAffectation();
            $nouvelleAffectation->setDossier($dossier);
            $nouvelleAffectation->setDateCommission($dateCommission);
            $nouvelleAffectation->setNumero(0);
            $this->entityManager->persist($nouvelleAffectation);

            // Dénormalisation (dateCommission est string, dateVisite est DateTime)
            $this->denormaliserDate($dossier, $colonneDate, $dateCommission->getDate());

            return;
        }

        // Cas 2.2 : Affectation existe déjà → Vérifier si date changée
        $affectationDateCommission = $affectationExistante->getDateCommission();
        if (!$affectationDateCommission instanceof DateCommission || $affectationDateCommission->getId() === $idDateCommission) {
            // Même date → Rien à faire
            return;
        }

        // Date changée → UPDATE avec reset heures/numéro
        $affectationExistante->setDateCommission($dateCommission);
        $affectationExistante->setHeureDebut(null);
        $affectationExistante->setHeureFin(null);
        $affectationExistante->setNumero(0);

        // Dénormalisation (dateCommission est string, dateVisite est DateTime)
        $this->denormaliserDate($dossier, $colonneDate, $dateCommission->getDate());
    }

    /**
     * Dénormalise une date dans les colonnes Dossier.
     *
     * dateCommission : stockée en string format dd/mm/yyyy (legacy)
     * dateVisite : stockée en DateTime natif
     *
     * @param Dossier                 $dossier     Dossier à mettre à jour
     * @param string                  $colonneDate Nom propriété ('dateCommission' ou 'dateVisite')
     * @param \DateTimeInterface|null $date        Date source (null = effacement)
     */
    private function denormaliserDate(Dossier $dossier, string $colonneDate, ?\DateTimeInterface $date): void
    {
        $setter = 'set'.ucfirst($colonneDate);

        // Si date null → effacement
        if (null === $date) {
            $dossier->$setter(null);

            return;
        }

        // dateCommission nécessite conversion string Y-m-d
        if ('dateCommission' === $colonneDate) {
            $dossier->$setter($date->format('Y-m-d'));

            return;
        }

        // dateVisite stocke le DateTime directement
        $dossier->$setter($date);
    }
}
