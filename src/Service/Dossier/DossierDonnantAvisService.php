<?php

namespace App\Service\Dossier;

use App\Constant\DossierNature;
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
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var DossierRepository */
    private $dossierRepository;

    /** ID statut établissement "ouvert" (pour répercussion aux enfants) */
    private const STATUT_OUVERT = 2;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        DossierRepository $dossierRepository
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->dossierRepository = $dossierRepository;
    }

    /**
     * Affecte le dossier comme "donnant avis" aux établissements concernés.
     *
     * Logique :
     * 1. Vérifie que le dossier donne avis (nature dans liste)
     * 2. Pour chaque établissement lié au dossier :
     *    - Compare date nouveau dossier vs ancien dossier donnant avis
     *    - Met à jour uniquement si date >= (évite régression)
     *    - Si repercuterAvis = true : répercute aux enfants (statut ouvert uniquement)
     *
     * @param Dossier $dossier        Dossier à affecter
     * @param bool    $repercuterAvis Si true, répercute aux établissements enfants (cellules)
     *
     * @return Etablissement[] Liste des établissements mis à jour (pour alertes emails)
     */
    public function affecterDossierDonnantAvis(Dossier $dossier, bool $repercuterAvis = false): array
    {
        // Vérifier que le dossier donne avis
        if (!$dossier->donneAvis()) {
            $this->logger->debug('Dossier ne donne pas avis, skip affectation', [
                'dossier_id' => $dossier->getId(),
                'nature_id' => $dossier->getNature() instanceof DossierNatureListe ? $dossier->getNature()->getId() : null,
            ]);

            return [];
        }

        $etablissementsModifies = [];

        // Itérer sur les établissements liés au dossier
        foreach ($dossier->getEtablissements() as $etablissement) {
            if ($this->devraitMettreAJour($etablissement, $dossier)) {
                $etablissement->setDossierDonnantAvis($dossier);
                $this->entityManager->persist($etablissement);
                $etablissementsModifies[] = $etablissement;

                $this->logger->info('Établissement mis à jour avec dossier donnant avis', [
                    'etablissement_id' => $etablissement->getId(),
                    'dossier_id' => $dossier->getId(),
                ]);

                // Répercuter aux enfants si demandé
                if ($repercuterAvis) {
                    $enfantsModifies = $this->repercuterAuxEnfants($etablissement, $dossier);
                    $etablissementsModifies = array_merge($etablissementsModifies, $enfantsModifies);
                }
            }
        }

        if ([] !== $etablissementsModifies) {
            $this->entityManager->flush();

            // @todo CACHE : Invalidation cache établissements via LegacyController
            // Le cache Zend doit être vidé pour chaque établissement modifié et son parent
            // Format clé cache : 'etablissement_' . $etablissementId
            // Doit être fait via pattern LegacyController pour accès au cache Zend
        }

        return $etablissementsModifies;
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
        if (null === $ancienDossier) {
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
     * @return Etablissement[] Liste des enfants mis à jour (pour alertes emails)
     */
    private function repercuterAuxEnfants(Etablissement $etablissementParent, Dossier $dossier): array
    {
        $enfantsModifies = [];

        // Récupérer les enfants (Collection fils)
        foreach ($etablissementParent->getFils() as $enfant) {
            // Vérifier que l'enfant a des informations (relation avec statut)
            try {
                $latestInfo = $enfant->getLatestInformations();
            } catch (\OutOfBoundsException $e) {
                // Pas d'informations pour cet enfant, skip
                continue;
            }

            // Vérifier statut = 2 (ouvert)
            $statut = $latestInfo->getStatut();
            if (null === $statut) {
                continue;
            }

            if (self::STATUT_OUVERT !== $statut->getId()) {
                continue;
            }

            // Mettre à jour l'enfant
            $enfant->setDossierDonnantAvis($dossier);
            $this->entityManager->persist($enfant);
            $enfantsModifies[] = $enfant;

            $this->logger->info('Enfant mis à jour avec dossier donnant avis (répercussion)', [
                'enfant_id' => $enfant->getId(),
                'parent_id' => $etablissementParent->getId(),
                'dossier_id' => $dossier->getId(),
            ]);
        }

        return $enfantsModifies;
    }

    /**
     * Récupère la date pertinente d'un dossier pour comparaison.
     *
     * Ordre de priorité : dateCommission > dateVisite > dateInsert
     *
     * @param Dossier $dossier Dossier dont extraire la date
     *
     * @return \DateTimeInterface Date du dossier
     *
     * @throws \RuntimeException Si aucune date valide n'est trouvée
     */
    private function getDateDossier(Dossier $dossier): \DateTimeInterface
    {
        // Priorité 1 : dateCommission (string dd/mm/yyyy à convertir)
        if (null !== $dossier->getDateCommission() && '' !== $dossier->getDateCommission()) {
            $dateCommission = \DateTime::createFromFormat('d/m/Y', $dossier->getDateCommission());
            if (false !== $dateCommission) {
                return $dateCommission;
            }
        }

        // Priorité 2 : dateVisite
        if (null !== $dossier->getDateVisite()) {
            return $dossier->getDateVisite();
        }

        // Priorité 3 : dateInsert (toujours définie à la création)
        $dateInsert = $dossier->getDateInsert();
        if (null !== $dateInsert) {
            return $dateInsert;
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
     */
    public function desaffecterDossierDonnantAvis(Dossier $dossier, DossierNatureListe $ancienneNature): void
    {
        // Early return : Ancienne nature ne donnait pas avis, rien à faire
        $ancienneDonnaitAvis = in_array(
            $ancienneNature->getId(),
            DossierNature::NATURES_DONNANT_AVIS,
            true
        );

        if (!$ancienneDonnaitAvis) {
            return;
        }

        $this->logger->info('Désaffectation dossier donnant avis (changement nature)', [
            'dossier_id' => $dossier->getId(),
            'ancienne_nature_id' => $ancienneNature->getId(),
            'nouvelle_nature_id' => $dossier->getNature() instanceof DossierNatureListe ? $dossier->getNature()->getId() : null,
        ]);

        $etablissementsModifies = false;

        // Pour chaque établissement lié au dossier
        foreach ($dossier->getEtablissements() as $etablissement) {
            // Chercher le dernier dossier valide donnant avis pour cet établissement
            $dernierDossierValide = $this->dossierRepository->findLastDossierDonnantAvisForEtablissement($etablissement);

            // Mettre à jour l'établissement
            $etablissement->setDossierDonnantAvis($dernierDossierValide);
            $this->entityManager->persist($etablissement);
            $etablissementsModifies = true;

            $this->logger->info('Établissement mis à jour après désaffectation', [
                'etablissement_id' => $etablissement->getId(),
                'dernier_dossier_valide_id' => $dernierDossierValide ? $dernierDossierValide->getId() : null,
            ]);
        }

        if ($etablissementsModifies) {
            $this->entityManager->flush();

            // @todo CACHE : Invalidation cache établissements via LegacyController
            // Le cache Zend doit être vidé pour chaque établissement modifié et son parent
            // Format clé cache : 'etablissement_' . $etablissementId
            // Doit être fait via pattern LegacyController pour accès au cache Zend
        }
    }
}
