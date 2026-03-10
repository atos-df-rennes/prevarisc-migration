<?php

namespace App\Doctrine;

use Doctrine\Common\Collections\Criteria;

final class ContactCriteria
{
    public static function sortedByName(): Criteria
    {
        return Criteria::create()
            ->orderBy(['nom' => Criteria::ASC, 'prenom' => Criteria::ASC]);
    }
}
