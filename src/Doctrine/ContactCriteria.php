<?php

namespace App\Doctrine;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;

final class ContactCriteria
{
    public static function sortedByName(): Criteria
    {
        return Criteria::create(true)
            ->orderBy(['nom' => Order::Ascending, 'prenom' => Order::Ascending]);
    }
}
