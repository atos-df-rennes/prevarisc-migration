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

        $dossierType = $dossier->getType();
        if (null === $dossierType) {
            $this->logger->error('Le type de dossier est null.', [
                'id_dossier' => $dossierId
            ]);

            return null;
        }

        $dossierTypeId = $dossierType->getId();

        // Pour les études (type 1), on s'attend toujours à un retour
        if (1 === $dossierTypeId) {
            if (null === $dateCommission) {
                $this->logger->error('La date de commission est null.', [
                    'id_dossier' => $dossierId
                ]);

                return null;
            }

            return $dateCommission->getId();
        }

        // Pour les visites et groupes de visites (types 2 et 3),
        // on s'attend à un retour seulement si le type d'événement est 1 (commission en salle)
        if (in_array($dossierTypeId, [2, 3], true)) {
            if (null === $dateCommission) {
                $this->logger->error('La date de commission est null.', [
                    'id_dossier' => $dossierId
                ]);

                return null;
            }

            $dateCommissionTypeEvenement = $dateCommission->getTypeEvenement();
            if (null === $dateCommissionTypeEvenement) {
                $this->logger->error('Le type d\'événement de la date de commission est null.', [
                    'id_dossier' => $dossierId,
                    'id_date_commission' => $dateCommission->getId()
                ]);

                return null;
            }

            $dateCommissionTypeId = $dateCommissionTypeEvenement->getId();
            if (1 === $dateCommissionTypeId) {
                return $dateCommission->getId();
            }
        }

        return null;
    }
}
