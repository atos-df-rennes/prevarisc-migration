<?php

namespace App\Dto;

final class AvisDerogationEtablissement
{
    /** @var int */
    private $idDossier;
    /** @var int */
    private $id;
    /** @var string  */
    private $titre;
    /** @var string  */
    private $type;
    /** @var bool  */
    private $leve;
    /** @var string|null  */
    private $debut;
    /** @var int|null */
    private $typeDossier;

    public function __construct(
        int $idDossier,
        int $id,
        string $titre,
        string $type,
        bool $leve,
        ?string $debut,
        ?int $typeDossier
    ){
        $this->idDossier = $idDossier;
        $this->id = $id;
        $this->titre = $titre;
        $this->type = $type;
        $this->leve = $leve;
        $this->debut = $debut;
        $this->typeDossier = $typeDossier;
    }

    public function idDossier(): int
    {
        return $this->idDossier;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function debut(): ?string
    {
        return $this->debut;
    }
    public function typeDossier(): ?int
    {
        return $this->typeDossier;
    }

    public function titre(): string
    {
        return $this->titre;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function leve(): bool
    {
        return $this->leve;
    }
}
