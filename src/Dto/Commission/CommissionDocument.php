<?php

namespace App\Dto\Commission;

use App\Dto\Document;

final readonly class CommissionDocument
{
    /**
     * @param Document[]|null $documents
     */
    public function __construct(private int $id, private string $nom, private string $type, private ?array $documents)
    {
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
