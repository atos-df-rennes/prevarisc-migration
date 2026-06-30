<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\UtilisateurInformations;

/**
 * DTO résultat de EtablissementRepository::findContactsWithInheritance().
 *
 * Sépare les contacts propres à l'établissement des contacts hérités de ses parents
 * (RG-CTT-009 : héritage sur 2 niveaux).
 */
final class EtablissementContactsDto
{
    /**
     * Contacts directs de l'établissement.
     *
     * @var UtilisateurInformations[]
     */
    public $contacts;

    /**
     * Contacts hérités des parents (niveau 1) et grands-parents (niveau 2).
     *
     * @var UtilisateurInformations[]
     */
    public $contactsParents;

    /**
     * @param UtilisateurInformations[] $contacts
     * @param UtilisateurInformations[] $contactsParents
     */
    public function __construct(array $contacts = [], array $contactsParents = [])
    {
        $this->contacts = $contacts;
        $this->contactsParents = $contactsParents;
    }
}
