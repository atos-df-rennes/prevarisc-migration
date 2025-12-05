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

        // Types non gérés (autres que 1, 2 ou 3)
        if (!in_array($dossierTypeId, [1, 2, 3], true)) {
            return null;
        }

        if (null === $dateCommission) {
            $this->logger->error('La date de commission est null.', [
                'id_dossier' => $dossierId
            ]);

            return null;
        }

        // Pour les visites et groupes de visites (types 2 et 3),
        // on vérifie d'abord le type d'événement
        if (in_array($dossierTypeId, [2, 3], true)) {
            $dateCommissionTypeEvenement = $dateCommission->getTypeEvenement();
            if (null === $dateCommissionTypeEvenement) {
                $this->logger->error('Le type d\'événement de la date de commission est null.', [
                    'id_dossier' => $dossierId,
                    'id_date_commission' => $dateCommission->getId()
                ]);

                return null;
            }

            // On ne s'attend à un retour que si le type d'événement est 1 (commission en salle)
            if (1 !== $dateCommissionTypeEvenement->getId()) {
                return null;
            }
        }

        // Ici, on a soit une étude (type 1), soit une visite/groupe (types 2 ou 3) avec type événement 1
        // Dans tous ces cas, on s'attend à une date de commission
        return $dateCommission->getId();
    }
}
