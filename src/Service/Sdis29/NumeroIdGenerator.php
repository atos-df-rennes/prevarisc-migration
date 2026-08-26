<?php

declare(strict_types=1);

namespace App\Service\Sdis29;

use App\Constant\GenreEtablissement;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Génère un numéro d'identifiant d'établissement selon le schéma de
 * numérotation propre à SDIS29 (plages par arrondissement, puis suffixes
 * numériques pour les cellules et alphabétiques pour les autres établissements
 * enfants).
 *
 * Portage de SDIS29_EtablissementController::generateidAction()
 * (legacy Zend, modules/sdis29/controllers/EtablissementController.php).
 */
class NumeroIdGenerator
{
    private const RANGES = [
        'Brest' => [1000, 39999],
        'Quimper' => [40000, 79999],
        'Morlaix' => [80000, 89999],
        'Châteaulin' => [90000, 99999],
    ];

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws \InvalidArgumentException si l'arrondissement est inconnu (uniquement utilisé sans idPere)
     */
    public function generate(string $arrondissement, string $idGenre, ?int $idPere): string
    {
        if (null === $idPere) {
            return $this->generateRacine($arrondissement, $idGenre);
        }

        return $this->generateEnfant($idPere, $idGenre);
    }

    private function generateRacine(string $arrondissement, string $idGenre): string
    {
        if (!isset(self::RANGES[$arrondissement])) {
            throw new \InvalidArgumentException(sprintf('Arrondissement inconnu : "%s".', $arrondissement));
        }

        [$minId, $maxId] = self::RANGES[$arrondissement];

        $conn = $this->entityManager->getConnection();
        $sql = 'SELECT CAST(SUBSTRING(e.NUMEROID_ETABLISSEMENT, 1, 5) AS UNSIGNED) + 1 AS numeroid
            FROM etablissement e
            WHERE CAST(SUBSTRING(e.NUMEROID_ETABLISSEMENT, 1, 5) AS UNSIGNED) + 1 BETWEEN :minId AND :maxId
            AND LENGTH(SUBSTRING(e.NUMEROID_ETABLISSEMENT, 1, 5)) = 5
            AND CAST(SUBSTRING(e.NUMEROID_ETABLISSEMENT, 1, 5) AS UNSIGNED) + 1 NOT IN (
                SELECT CAST(SUBSTRING(e2.NUMEROID_ETABLISSEMENT, 1, 5) AS UNSIGNED) FROM etablissement AS e2
            )
            ORDER BY 1 ASC
            LIMIT 1';

        $rawNumeroid = $conn->fetchOne($sql, ['minId' => $minId, 'maxId' => $maxId]);
        $numeroid = str_pad(is_scalar($rawNumeroid) ? (string) $rawNumeroid : '', 5, '0', STR_PAD_LEFT);

        return (string) GenreEtablissement::CELLULE === $idGenre ? $numeroid.'.00' : $numeroid;
    }

    private function generateEnfant(int $idPere, string $idGenre): string
    {
        $conn = $this->entityManager->getConnection();

        $rawNumeroPere = $conn->fetchOne(
            'SELECT NUMEROID_ETABLISSEMENT AS numeroid FROM etablissement WHERE ID_ETABLISSEMENT = :idPere',
            ['idPere' => $idPere]
        );
        $numeroPere = is_scalar($rawNumeroPere) ? (string) $rawNumeroPere : '';

        // Genre "Cellule" (3) : on cherche parmi les enfants partageant le numéro complet du père,
        // les autres genres : uniquement sur les 5 premiers caractères (racine commune).
        $numeroSearch = (string) GenreEtablissement::CELLULE === $idGenre ? $numeroPere : substr($numeroPere, 0, 5);
        $length = strlen($numeroSearch);

        $rows = $conn->fetchAllAssociative(
            'SELECT NUMEROID_ETABLISSEMENT AS numeroid FROM etablissement e
                WHERE SUBSTRING(e.NUMEROID_ETABLISSEMENT, 1, :length) = :numeroSearch',
            ['length' => $length, 'numeroSearch' => $numeroSearch]
        );

        if ((string) GenreEtablissement::CELLULE === $idGenre) {
            return $this->nextCelluleSuffix($rows, $numeroPere);
        }

        return $this->nextLetterSuffix($rows, $numeroSearch);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function nextCelluleSuffix(array $rows, string $numeroPere): string
    {
        $compteur = 0;
        foreach ($rows as $row) {
            $numeroid = is_scalar($row['numeroid']) ? (string) $row['numeroid'] : '';
            $parts = explode('.', $numeroid);
            $suffix = end($parts);
            if (2 === strlen($suffix) && (int) $suffix > $compteur) {
                $compteur = (int) $suffix;
            }
        }

        return $numeroPere.'.'.sprintf('%02u', $compteur + 1);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function nextLetterSuffix(array $rows, string $numeroSearch): string
    {
        $compteur = \ord('A') - 1;
        foreach ($rows as $row) {
            $numeroid = is_scalar($row['numeroid']) ? (string) $row['numeroid'] : '';
            $parts = explode('.', $numeroid);
            $suffix = end($parts);
            if (1 === strlen($suffix) && \ord($suffix) > $compteur) {
                $compteur = \ord($suffix);
            }
        }

        return $numeroSearch.'.'.\chr($compteur + 1);
    }
}
