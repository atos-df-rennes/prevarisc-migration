<?php

namespace App\Service\Search;

use Doctrine\DBAL\Connection;

/**
 * Helper de construction de conditions SQL pour les services de recherche.
 * Extrait de Search (refactoring REF-SVC-01) pour permettre la testabilité unitaire.
 */
class SearchQueryHelper
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * Construit une condition WHERE de type égalité ou LIKE.
     *
     * @param array<int|string, bool|int|string>|bool|float|int|string $value
     */
    public function buildCriteria(string $key, $value, bool $exact = true): string
    {
        if ([] === $value) {
            throw new \InvalidArgumentException('buildCriteria() requires a non-empty value.');
        }

        $parts = [];

        $values = is_array($value) ? $value : [$value];

        foreach ($values as $singleValue) {
            $stringValue = is_bool($singleValue) ? (string) (int) $singleValue : (string) $singleValue;
            $quotedVal = $this->safeQuote(
                $exact ? $stringValue : '%'.$stringValue.'%'
            );
            $parts[] = $key.($exact ? '=' : ' LIKE ').$quotedVal;
        }

        return '('.implode(' OR ', $parts).')';
    }

    /**
     * Enveloppe Connection::quote() pour garantir un retour string (DBAL 2.x non typé).
     */
    public function safeQuote(string $value): string
    {
        $quoted = $this->connection->quote($value);

        if (!is_string($quoted)) {
            throw new \RuntimeException('Connection::quote() returned a non-string value — cannot safely quote SQL parameter.');
        }

        return $quoted;
    }
}
