<?php

namespace App\Service\Commission;

use App\Dto\Commission\CommissionDocument;
use App\Entity\Commission;
use App\Service\Documents;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

final readonly class CommissionDocumentBuilder
{
    public function __construct(private Documents $documents, private LoggerInterface $logger)
    {
    }

    /**
     * @param Commission[] $commissions
     *
     * @return CommissionDocument[]
     */
    public function buildWithCommissions(array $commissions): array
    {
        $documents = [];

        foreach ($commissions as $commission) {
            $commissionId = $commission->getId();
            $commissionLibelle = $commission->getLibelle();
            $commissionType = $commission->getType();
            $commissionTypeLibelle = $commissionType?->getLibelle();

            if (in_array(null, [$commissionId, $commissionLibelle, $commissionType, $commissionTypeLibelle], true)) {
                $this->logger->error("La commission n'a pas été trouvée.", [
                    'commission_id' => $commissionId,
                    'commission_libelle' => $commissionLibelle,
                    'commission_type' => $commissionType,
                    'commission_type_libelle' => $commissionTypeLibelle
                ]);

                continue;
            }

            try {
                $documents[] = new CommissionDocument(
                    $commissionId,
                    $commissionLibelle,
                    $commissionTypeLibelle,
                    $this->documents->findForCommission($commissionId)
                );
            } catch (DirectoryNotFoundException $directoryNotFoundException) {
                $this->logger->error($directoryNotFoundException->getMessage(), [
                    'commission_id' => $commissionId,
                    'commission_libelle' => $commissionLibelle,
                    'commission_type' => $commissionType,
                    'commission_type_libelle' => $commissionTypeLibelle
                ]);

                $documents[] = new CommissionDocument(
                    $commissionId,
                    $commissionLibelle,
                    $commissionTypeLibelle,
                    null
                );
            }
        }

        return $documents;
    }
}
