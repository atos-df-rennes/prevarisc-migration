<?php

namespace App\Service;

use App\Entity\DateCommission;
use App\Entity\Dossier;
use Psr\Log\LoggerInterface;

final class DateAffectation
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /*
     * Retourne l'identifiant de la date de commission affectée au dossier.
     * Retourne null si aucune condition n'est validée.
     * */
    public function getIdDateCommission(Dossier $dossier, ?DateCommission $dateCommission): ?int
    {
        $dossierId = $dossier->getId();

        if (null === $dateCommission) {
            $this->logger->error('La date de commission est null.', [
                'id_dossier' => $dossierId
            ]);

            return null;
        }

        $dossierType = $dossier->getType();
        if (null === $dossierType) {
            $this->logger->error('Le type du dossier est null.', [
                'id_dossier' => $dossierId
            ]);

            return null;
        }

        $dossierTypeId = $dossierType->getId();
        if (1 === $dossierTypeId) {
            return $dateCommission->getId();
        }

        $dateCommissionTypeEvenement = $dateCommission->getTypeEvenement();
        if (null === $dateCommissionTypeEvenement) {
            $this->logger->error('Le type de date de commission est null.', [
                'id_dossier' => $dossierId,
                'id_date_commission' => $dateCommission->getId()
            ]);

            return null;
        }

        $dateCommissionTypeId = $dateCommissionTypeEvenement->getId();
        if (
            in_array($dossierTypeId, [2, 3], true)
            && 1 === $dateCommissionTypeId
        ) {
            return $dateCommission->getId();
        }

        return null;
    }
}
