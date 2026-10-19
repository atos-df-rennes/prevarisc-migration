<?php

namespace App\Service\Search;

use Doctrine\DBAL\Connection;

/**
 * Helper de construction de conditions SQL pour les services de recherche.
 * Extrait de Search (refactoring REF-SVC-01) pour permettre la testabilité unitaire.
 */
final readonly class SearchQueryHelper
{
    public function __construct(private Connection $connection)
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
     * Sépare un paramètre de recherche "label" en couple [label, identifiant].
     *
     * Reproduit le comportement legacy (SDIS62 SearchController::etablissementAction()) :
     * si le label commence par '#', il s'agit d'une recherche par identifiant exact
     * (NUMEROID_ETABLISSEMENT) et non d'une recherche textuelle libre. Le '#' est retiré
     * et la valeur restante est renvoyée comme identifiant, le label devenant null.
     * Sinon, le label est renvoyé tel quel et l'identifiant est null.
     *
     * @return array{0: string|null, 1: string|null} [label, identifiant]
     */
    public function splitLabelAndIdentifiant(?string $label): array
    {
        if (null === $label || '' === $label) {
            return [$label, null];
        }

        if (str_starts_with($label, '#')) {
            return [null, substr($label, 1)];
        }

        return [$label, null];
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
