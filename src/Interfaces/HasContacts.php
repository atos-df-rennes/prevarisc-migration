<?php

namespace App\Interfaces;

use App\Entity\UtilisateurInformations;

interface HasContacts
{
    public function findNumberByContacts(UtilisateurInformations $contact): int;
}
