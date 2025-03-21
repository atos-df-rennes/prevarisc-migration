<?php

namespace App\ValueObject;

final class ClesTableauDesPeriodicites
{
    /** @var int */
    private $categorie;

    /** @var string */
    private $type;

    /** @var bool */
    private $localSommeil;

    private function __construct(string $categorie, string $type, string $localSommeil)
    {
        if (false === ($validatedCategorie = filter_var($categorie, FILTER_VALIDATE_INT))) {
            throw new \InvalidArgumentException("Le type de la catégorie n'est pas correct. Type attendu : entier.");
        }

        $this->categorie = $validatedCategorie;
        $this->type = $type;
        $this->localSommeil = filter_var($localSommeil, FILTER_VALIDATE_BOOLEAN);
    }

    public static function fromRequest(string $requestKey): self
    {
        return new self(...explode('_', $requestKey));
    }

    public function categorie(): int
    {
        return $this->categorie;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function localSommeil(): bool
    {
        return $this->localSommeil;
    }
}
