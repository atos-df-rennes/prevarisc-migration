<?php

namespace App\Dto\Commission;

use App\Dto\Document;

final class CommissionDocument
{
    /**
     * @param Document[]|null $documents
     */
    public function __construct(private readonly int $id, private readonly string $nom, private readonly string $type, private readonly ?array $documents)
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
