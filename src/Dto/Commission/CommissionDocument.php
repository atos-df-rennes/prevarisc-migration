<?php

namespace App\Dto\Commission;

use App\Dto\Document;

final class CommissionDocument
{
    private int $id;

    private string $nom;

    private string $type;

    /** @var Document[]|null */
    private ?array $documents;

    /**
     * @param Document[]|null $documents
     */
    public function __construct(int $id, string $nom, string $type, ?array $documents)
    {
        $this->id = $id;
        $this->nom = $nom;
        $this->type = $type;
        $this->documents = $documents;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return Document[]|null
     */
    public function getDocuments(): ?array
    {
        return $this->documents;
    }
}
