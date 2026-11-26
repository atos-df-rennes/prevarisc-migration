<?php

declare(strict_types=1);

namespace App\Platau;

final readonly class HttpNotificationReader extends PlatauApiReader
{
    /** @return list<mixed> Les éléments sont vérifiés individuellement, sans rejeter le lot. */
    public function batch(?int $offset): array
    {
        $data = $this->read('GET', 'notifications', null === $offset ? [] : ['query' => ['offset' => $offset]]);
        $rows = $data['notifications'] ?? null;
        if (!empty($data['erreurs']) || !is_array($rows) || !array_is_list($rows)) {
            throw new PlatauException("La réponse de Plat'AU ne contient pas de lot de notifications exploitable.");
        }

        return $rows;
    }

    public function nomenclature(string $code, int $id): string
    {
        $rows = $this->read('GET', 'nomenclatures/'.$code, []);
        if (!array_is_list($rows)) {
            throw new PlatauException('Un problème a eu lieu dans la récupération des résultats de recherche de nomenclatures : le résultat est incorrect');
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new PlatauException('Un problème a eu lieu dans la récupération des résultats de recherche de nomenclatures : le résultat est incorrect');
            }

            $rowId = $row['idNom'] ?? null;
            if ((!is_int($rowId) && (!is_string($rowId) || !ctype_digit($rowId))) || (int) $rowId !== $id) {
                continue;
            }

            if (!array_key_exists('libNom', $row)) {
                throw new PlatauException('Un problème a eu lieu dans la récupération des résultats de recherche de nomenclature : clé libNom introuvable');
            }
            if (!is_string($row['libNom'])) {
                throw new PlatauException('Le format de la valeur de la clé libNom est incorrect');
            }

            return $row['libNom'];
        }

        throw new PlatauException(sprintf("Aucune nomenclature trouvée pour l'identifiant %d et le code %s", $id, $code));
    }

    public function actorDesignation(?string $id): ?string
    {
        if (null === $id) {
            return null;
        }

        if ('' === trim($id)) {
            throw new PlatauException("Identifiant d'acteur vide.");
        }

        // Recherche par identifiant unique : la première page suffit.
        $data = $this->read('POST', 'acteurs/recherche', [
            'query' => ['numeroPage' => 0, 'nbElementsParPage' => 100],
            'json' => ['idActeur' => $id],
        ]);
        $rows = $data['resultats'] ?? null;
        $actor = is_array($rows) && array_is_list($rows) && 1 === count($rows) ? $rows[0] : null;
        if (!empty($data['erreur']) || !is_array($actor) || $id !== ($actor['idActeur'] ?? null)
            || !is_string($actor['designationActeur'] ?? null) || '' === trim($actor['designationActeur'])) {
            throw new PlatauException("Acteur introuvable ou réponse de Plat'AU incorrecte.");
        }

        return $actor['designationActeur'];
    }
}
