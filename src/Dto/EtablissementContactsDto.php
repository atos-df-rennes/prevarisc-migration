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
     * @param UtilisateurInformations[] $contacts
     * @param UtilisateurInformations[] $contactsParents
     */
    public function __construct(
        public array $contacts = [],
        public array $contactsParents = []
    ) {
    }
}
