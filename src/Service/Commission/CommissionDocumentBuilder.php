<?php

namespace App\Service\Commission;

use App\Dto\Commission\CommissionDocument;
use App\Service\Documents;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

final class CommissionDocumentBuilder
{
    /** @var Documents */
    private $documents;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Documents $documents, LoggerInterface $logger)
    {
        $this->documents = $documents;
        $this->logger = $logger;
    }

    /**
     * @param \App\Entity\Commission[] $commissions
     *
     * @return \App\Dto\Commission\CommissionDocument[]
     */
    public function buildWithCommissions(array $commissions): array
    {
        $documents = [];

        foreach ($commissions as $commission) {
            $commissionId = $commission->getId();
            $commissionLibelle = $commission->getLibelle();
            $commissionType = $commission->getType();
            $commissionTypeLibelle = null !== $commission->getType() ? $commissionType->getLibelle() : null;

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
                    $commission->getId(),
                    $commission->getLibelle(),
                    $commission->getType()->getLibelle(),
                    $this->documents->findForCommission($commission->getId())
                );
            } catch (DirectoryNotFoundException $directoryNotFoundException) {
                $this->logger->error($directoryNotFoundException->getMessage(), [
                    'commission_id' => $commissionId,
                    'commission_libelle' => $commissionLibelle,
                    'commission_type' => $commissionType,
                    'commission_type_libelle' => $commissionTypeLibelle
                ]);

                $documents[] = new CommissionDocument(
                    $commission->getId(),
                    $commission->getLibelle(),
                    $commission->getType()->getLibelle(),
                    null
                );
            }
        }

        return $documents;
    }
}
