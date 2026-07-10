<?php

namespace App\Service\Commission;

use App\Entity\Commission;
use App\Entity\CommissionMembre;
use App\Entity\UtilisateurInformations;
use App\Service\Contact;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion des commissions : création, mise à jour et suppression,
 * avec synchronisation du filesystem associé.
 */
final class CommissionManagerService
{
    private EntityManagerInterface $entityManager;

    private CommissionFilesystem $commissionFilesystem;

    private CommissionMembreCourrierService $courrierService;

    private Contact $contactService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommissionFilesystem $commissionFilesystem,
        CommissionMembreCourrierService $courrierService,
        Contact $contactService
    ) {
        $this->entityManager = $entityManager;
        $this->commissionFilesystem = $commissionFilesystem;
        $this->courrierService = $courrierService;
        $this->contactService = $contactService;
    }

    /**
     * Sauvegarde les commissions soumises et supprime celles absentes de la soumission.
     * Synchronise ensuite le filesystem (création/suppression de répertoires).
     *
     * Alignement legacy (CommissionController::deleteAction) :
     * - Les contacts de la commission sont dissociés ; ceux non utilisés ailleurs sont supprimés.
     * - Les membres de la commission sont supprimés via cascade Doctrine.
     * - Les règles de compétence sont supprimées via orphanRemoval.
     * - Les fichiers ODT des membres sont supprimés du filesystem après le flush.
     *
     * Note d'intégrité : en cas d'échec filesystem après le flush, la base de données
     * reste authoritative. Les répertoires manquants peuvent être recréés manuellement.
     * Probabilité : très faible (échec DB est davantage susceptible d'arriver que l'inverse).
     *
     * @param Commission[] $originalCommissions  Commissions avant soumission du formulaire
     * @param Commission[] $submittedCommissions Commissions issues du formulaire
     *
     * @throws \Exception Si la persistance en base échoue
     */
    public function saveCommissions(array $originalCommissions, array $submittedCommissions): void
    {
        foreach ($submittedCommissions as $commission) {
            $this->entityManager->persist($commission);
        }

        $submittedIds = [];
        foreach ($submittedCommissions as $commission) {
            if ($commission->getId()) {
                $submittedIds[] = $commission->getId();
            }
        }

        /** @var int[] $commissionsToDelete */
        $commissionsToDelete = [];

        /**
         * Membres dont les fichiers ODT doivent être supprimés après le flush.
         *
         * @var CommissionMembre[] $membresOdtToClean
         */
        $membresOdtToClean = [];

        foreach ($originalCommissions as $commission) {
            $commissionId = $commission->getId();
            if (!$commissionId) {
                continue;
            }

            if (in_array($commissionId, $submittedIds, true)) {
                continue;
            }

            $commissionsToDelete[] = $commissionId;

            // Capture les membres avant que Doctrine ne les supprime en cascade
            foreach ($commission->getMembres() as $membre) {
                $membresOdtToClean[$membre->getId()] = $membre;
            }

            // Supprime les contacts de la commission :
            // si un contact n'est rattaché qu'à cette commission, il est supprimé.
            foreach ($commission->getContacts()->toArray() as $contact) {
                /** @var UtilisateurInformations $contact */
                $commission->removeContact($contact);
                if (!$this->contactService->isUsed($contact, Contact::COMMISSION)) {
                    $this->entityManager->remove($contact);
                }
            }

            $this->entityManager->remove($commission);
        }

        $this->entityManager->flush();

        foreach ($submittedCommissions as $commission) {
            if ($commission->getId()) {
                $this->commissionFilesystem->createCommissionDirectory($commission->getId());
            }
        }

        foreach ($commissionsToDelete as $commissionId) {
            $this->commissionFilesystem->removeCommissionDirectory($commissionId);
        }

        // Suppression des fichiers ODT après persistance réussie
        foreach ($membresOdtToClean as $id => $membre) {
            $this->courrierService->deleteAllFiles($membre, $id);
        }
    }
}
