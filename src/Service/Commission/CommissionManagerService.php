<?php

namespace App\Service\Commission;

use App\Entity\Commission;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion des commissions : création, mise à jour et suppression,
 * avec synchronisation du filesystem associé.
 */
final class CommissionManagerService
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CommissionFilesystem */
    private $commissionFilesystem;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommissionFilesystem $commissionFilesystem
    ) {
        $this->entityManager = $entityManager;
        $this->commissionFilesystem = $commissionFilesystem;
    }

    /**
     * Sauvegarde les commissions soumises et supprime celles absentes de la soumission.
     * Synchronise ensuite le filesystem (création/suppression de répertoires).
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
        foreach ($originalCommissions as $commission) {
            $commissionId = $commission->getId();
            if ($commissionId && !in_array($commissionId, $submittedIds, true)) {
                $commissionsToDelete[] = $commissionId;
                $this->entityManager->remove($commission);
            }
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
    }
}
