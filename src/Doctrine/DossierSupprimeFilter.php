<?php

namespace App\Doctrine;

use App\Entity\Dossier;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Filtre SQL personnalisé pour appliquer des contraintes sur l'entité Dossier.
 *
 * Cette classe permet de s'assurer que seuls les dossiers actifs (non supprimés) sont retournés par les requêtes.
 */
class DossierSupprimeFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (Dossier::class !== $targetEntity->getName()) {
            return '';
        }

        return sprintf('%s.DATESUPPRESSION_DOSSIER IS NULL', $targetTableAlias);
    }
}
