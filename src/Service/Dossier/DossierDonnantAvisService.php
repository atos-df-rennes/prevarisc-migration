<?php

namespace App\Service\Dossier;

use App\Constant\DossierNature;
use App\Constant\StatutEtablissement;
use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\Etablissement;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion des dossiers donnant avis pour les établissements.
 */
class DossierDonnantAvisService
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly LoggerInterface $logger, private readonly DossierRepository $dossierRepository)
    {
    }

    /**
     * Affecte le dossier comme "donnant avis" aux établissements concernés.
     *
     * Logique :
     * 1. Vérifie que le dossier donne avis (nature dans liste ET date pertinente renseignée)
     * 2. Pour chaque établissement lié au dossier :
     *    - Compare date nouveau dossier vs ancien dossier donnant avis
     *    - Met à jour uniquement si date >= (évite régression)
     *    - Si repercuterAvis = true : répercute aux enfants (statut ouvert uniquement)
     *
     * Legacy : isDossierDonnantAvis() vérifie DEUX conditions : nature ET présence de la date pertinente.
     *
     * @param Dossier $dossier        Dossier à affecter
     * @param bool    $repercuterAvis Si true, répercute aux établissements enfants (cellules)
     *
     * @return int[] Liste des IDs établissements modifiés (pour vidage cache + alertes emails)
     */
    public function affecterDossierDonnantAvis(Dossier $dossier, bool $repercuterAvis = false): array
    {
        // Vérifier que le dossier donne avis (nature dans liste)
        if (!$dossier->donneAvis()) {
            $this->logger->debug('Dossier ne donne pas avis, skip affectation', [
                'dossier_id' => $dossier->getId(),
                'nature_id' => $dossier->getNature() instanceof DossierNatureListe ? $dossier->getNature()->getId() : null,
            ]);

            return [];
        }

        // Vérifier que la date pertinente est renseignée (legacy isDossierDonnantAvis)
        // Ex: un groupe de visite sans dateCommission ne doit pas devenir donnant avis
        if (!$this->aRelevanteDatePourAvis($dossier)) {
            $this->logger->debug('Dossier sans date pertinente, skip affectation', [
                'dossier_id' => $dossier->getId(),
                'nature_id' => $dossier->getNature() instanceof DossierNatureListe ? $dossier->getNature()->getId() : null,
            ]);

            return [];
        }

        $etablissementsIds = [];

        // Itérer sur les établissements liés au dossier
        foreach ($dossier->getEtablissements() as $etablissement) {
            if ($this->devraitMettreAJour($etablissement, $dossier)) {
                $etablissement->setDossierDonnantAvis($dossier);
                $this->entityManager->persist($etablissement);
                $etablissementsIds[] = $etablissement->getId();

                $this->logger->info('Établissement mis à jour avec dossier donnant avis', [
                    'etablissement_id' => $etablissement->getId(),
                    'dossier_id' => $dossier->getId(),
                ]);

                // Répercuter aux enfants si demandé
                if ($repercuterAvis) {
                    $enfantsIds = $this->repercuterAuxEnfants($etablissement, $dossier);
                    $etablissementsIds = array_merge($etablissementsIds, $enfantsIds);
                }
            }
        }

        return $etablissementsIds;
    }

    /**
     * Vérifie si l'établissement devrait être mis à jour avec le nouveau dossier.
     *
     * Logique : Met à jour uniquement si :
     * - Aucun dossier donnant avis actuellement OU
     * - Date nouveau dossier >= date ancien dossier donnant avis
     *
     * @param Etablissement $etablissement  Établissement à vérifier
     * @param Dossier       $nouveauDossier Nouveau dossier candidat
     *
     * @return bool True si mise à jour nécessaire
     */
    private function devraitMettreAJour(Etablissement $etablissement, Dossier $nouveauDossier): bool
    {
        $ancienDossier = $etablissement->getDossierDonnantAvis();

        // Pas de dossier donnant avis actuel → OK pour mettre à jour
        if (!$ancienDossier instanceof Dossier) {
            return true;
        }

        // Comparer les dates des dossiers
        $dateAncien = $this->getDateDossier($ancienDossier);
        $dateNouveau = $this->getDateDossier($nouveauDossier);

        // Mettre à jour si date nouveau >= date ancien
        return $dateNouveau >= $dateAncien;
    }

    /**
     * Répercute le dossier donnant avis aux établissements enfants (cellules).
     *
     * Condition : Uniquement les enfants avec statut "ouvert" (ID_STATUT = 2).
     *
     * @param Etablissement $etablissementParent Établissement parent
     * @param Dossier       $dossier             Dossier à répercuter
     *
     * @return int[] Liste des IDs enfants mis à jour (pour vidage cache + alertes emails)
     */
    private function repercuterAuxEnfants(Etablissement $etablissementParent, Dossier $dossier): array
    {
        $enfantsIds = [];

        // Récupérer les enfants (Collection fils)
        foreach ($etablissementParent->getFils() as $enfant) {
            // Vérifier que l'enfant a des informations (relation avec statut)
            try {
                $latestInfo = $enfant->getLatestInformations();
            } catch (\OutOfBoundsException) {
                // Pas d'informations pour cet enfant, skip
                continue;
            }

            // Vérifier statut = 2 (ouvert)
            $statut = $latestInfo->getStatut();
            if (null === $statut) {
                continue;
            }

            if (StatutEtablissement::OUVERT !== $statut->getId()) {
                continue;
            }

            // Mettre à jour l'enfant
            $enfant->setDossierDonnantAvis($dossier);
            $this->entityManager->persist($enfant);
            $enfantsIds[] = $enfant->getId();

            $this->logger->info('Enfant mis à jour avec dossier donnant avis (répercussion)', [
                'enfant_id' => $enfant->getId(),
                'parent_id' => $etablissementParent->getId(),
                'dossier_id' => $dossier->getId(),
            ]);
        }

        return $enfantsIds;
    }

    /**
     * Vérifie si la date pertinente est renseignée pour que le dossier puisse donner avis.
     *
     * Logique legacy (isDossierDonnantAvis) :
     * - Visites (natures [21, 23, 24, 47]) → DATEVISITE_DOSSIER doit être renseignée
     * - Études et groupes (natures [7, 16, 17, 19, 26, 28, 29, 48]) → DATECOMM_DOSSIER doit être renseignée
     * Un dossier sans date pertinente ne peut pas devenir "donnant avis".
     *
     * @param Dossier $dossier Dossier à vérifier
     *
     * @return bool True si la date pertinente est présente
     */
    private function aRelevanteDatePourAvis(Dossier $dossier): bool
    {
        $nature = $dossier->getNature();
        $natureId = $nature instanceof DossierNatureListe ? $nature->getId() : null;

        if (null === $natureId) {
            return false;
        }

        if (in_array($natureId, DossierNature::NATURES_VISITES_COMMISSION, true)) {
            return $dossier->getDateVisite() instanceof \DateTimeInterface;
        }

        // Études et groupes → dateCommission
        return null !== $dossier->getDateCommission() && '' !== $dossier->getDateCommission();
    }

    /**
     * Récupère la date pertinente d'un dossier pour comparaison, selon sa nature.
     *
     * Logique legacy (getDateDossier) :
     * - Visites (natures [21, 23, 24, 47]) → dateVisite
     * - Études et groupes (natures [7, 16, 17, 19, 26, 28, 29, 48]) → dateCommission
     * - Fallback : dateInsert (toujours défini)
     *
     * @param Dossier $dossier Dossier dont extraire la date
     *
     * @return \DateTimeInterface Date du dossier
     *
     * @throws \RuntimeException Si aucune date valide n'est trouvée
     */
    private function getDateDossier(Dossier $dossier): \DateTimeInterface
    {
        $nature = $dossier->getNature();
        $natureId = $nature instanceof DossierNatureListe ? $nature->getId() : null;

        if (null !== $natureId && in_array($natureId, DossierNature::NATURES_VISITES_COMMISSION, true)) {
            // Visites → dateVisite en priorité
            if ($dossier->getDateVisite() instanceof \DateTimeInterface) {
                return new \DateTime($dossier->getDateVisite()->format('Y-m-d'))->setTime(0, 0, 0);
            }
        } elseif (null !== $dossier->getDateCommission() && '' !== $dossier->getDateCommission()) {
            // Études et groupes → dateCommission en priorité
            $dateCommission = \DateTime::createFromFormat('Y-m-d', $dossier->getDateCommission());
            if (false !== $dateCommission) {
                return $dateCommission->setTime(0, 0, 0);
            }
        }

        // Fallback : dateInsert (toujours définie à la création)
        if ($dossier->getDateInsert() instanceof \DateTimeInterface) {
            return new \DateTime($dossier->getDateInsert()->format('Y-m-d'))->setTime(0, 0, 0);
        }

        // Ne devrait jamais arriver (dateInsert est requis)
        throw new \RuntimeException('Impossible de récupérer une date pour le dossier '.$dossier->getId());
    }

    /**
     * Désaffecte le dossier courant et cherche le dernier dossier donnant avis valide.
     *
     * Cas d'usage : Quand on change la nature d'un dossier de "donnant avis" → "ne donnant PAS avis"
     * Il faut alors retrouver le dernier dossier valide donnant avis pour l'établissement.
     *
     * Logique legacy (lignes 1386-1403) :
     * - Vérifie qu'on passe d'une nature donnant avis à une nature ne donnant pas avis
     * - Pour chaque établissement : cherche le dernier dossier valide donnant avis
     * - Met à jour l'établissement avec ce dossier (ou null si aucun)
     *
     * @param Dossier            $dossier        Dossier en cours de modification
     * @param DossierNatureListe $ancienneNature Nature avant modification (garantie non-null par Controller)
     *
     * @return int[] Liste des IDs établissements modifiés (pour vidage cache legacy)
     */
    public function desaffecterDossierDonnantAvis(Dossier $dossier, DossierNatureListe $ancienneNature): array
    {
        // Early return : Ancienne nature ne donnait pas avis, rien à faire
        $ancienneDonnaitAvis = in_array(
            $ancienneNature->getId(),
            DossierNature::NATURES_DONNANT_AVIS,
            true
        );

        if (!$ancienneDonnaitAvis) {
            return [];
        }

        $this->logger->info('Désaffectation dossier donnant avis (changement nature)', [
            'dossier_id' => $dossier->getId(),
            'ancienne_nature_id' => $ancienneNature->getId(),
            'nouvelle_nature_id' => $dossier->getNature() instanceof DossierNatureListe ? $dossier->getNature()->getId() : null,
        ]);

        $etablissementsIds = [];

        // Pour chaque établissement lié au dossier
        foreach ($dossier->getEtablissements() as $etablissement) {
            // Chercher le dernier dossier valide donnant avis pour cet établissement
            // Exclure le dossier courant car il a encore sa nature donnant avis (pas encore flush)
            $dernierDossierValide = $this->dossierRepository->findLastDossierDonnantAvisForEtablissement(
                $etablissement,
                $dossier
            );

            // Mettre à jour l'établissement
            $etablissement->setDossierDonnantAvis($dernierDossierValide);
            $this->entityManager->persist($etablissement);
            $etablissementsIds[] = $etablissement->getId();

            $this->logger->info('Établissement mis à jour après désaffectation', [
                'etablissement_id' => $etablissement->getId(),
                'dernier_dossier_valide_id' => $dernierDossierValide instanceof Dossier ? $dernierDossierValide->getId() : null,
            ]);
        }

        return $etablissementsIds;
    }
}
