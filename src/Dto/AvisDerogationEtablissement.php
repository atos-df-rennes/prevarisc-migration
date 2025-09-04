<?php

namespace App\Dto;

final class AvisDerogationEtablissement
{
    /** @var string|null  */
    private $debut;
    /** @var int|null */
    private $type_dossier;
    /** @var string  */
    private $titre;
    /** @var string  */
    private $type;
    /** @var int|null  */
    private $leve;

    public function __construct(string $titre, string $type, ?string $debut, ?int $typeDossier, ?int $leve){
        $this->titre = $titre;
        $this->type = $type;
        $this->debut = $debut;
        $this->type_dossier = $typeDossier;
        $this->leve = $leve;
    }

    public function debut(): ?string
    {
        return $this->debut;
    }
    public function typeDossier(): ?int
    {
        return $this->type_dossier;
    }

    public function titre(): string
    {
        return $this->titre;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function leve(): ?int
    {
        return $this->leve;
    }
}
