<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Pré-correction avant Version20250305163248 :
 *  1. Déduplique ID_UTILISATEURINFORMATIONS dans adressecommune pour permettre
 *     la contrainte UNIQUE (les lignes pointaient toutes vers le même enregistrement).
 *  2. Normalise le nom de l'index sur groupement.ID_UTILISATEURINFORMATIONS
 *     afin que le DROP INDEX de Version20250305163248 puisse s'exécuter.
 */
final class Version20250305163100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Pré-correction structure/données avant Version20250305163248 (index groupement + unicité adressecommune).';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->fixAdressecommuneUnicite();
        $this->fixGroupementUnicite();
        $this->fixGroupementIndexName();
    }

    /**
     * Insère des lignes vides dans utilisateurinformations et réaffecte
     * un ID unique à chaque ligne d'adressecommune pour permettre
     * la future contrainte UNIQUE sur ID_UTILISATEURINFORMATIONS.
     */
    private function fixAdressecommuneUnicite(): void
    {
        // Si aucun doublon, la correction n'est pas nécessaire
        $duplicates = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM (
                SELECT ID_UTILISATEURINFORMATIONS
                FROM adressecommune
                WHERE ID_UTILISATEURINFORMATIONS IS NOT NULL
                GROUP BY ID_UTILISATEURINFORMATIONS
                HAVING COUNT(*) > 1
            ) t'
        );

        if (0 === $duplicates) {
            return;
        }

        /** @var array<int, array{NUMINSEE_COMMUNE: string}> $communes */
        $communes = $this->connection->fetchAllAssociative(
            'SELECT NUMINSEE_COMMUNE FROM adressecommune ORDER BY NUMINSEE_COMMUNE'
        );

        if (empty($communes)) {
            return;
        }

        $count = count($communes);

        $maxId = (int) $this->connection->fetchOne(
            'SELECT COALESCE(MAX(ID_UTILISATEURINFORMATIONS), 0) FROM utilisateurinformations'
        );

        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        // Insère $count lignes vides dans utilisateurinformations (une par commune).
        // Le cross-join génère jusqu'à 1000 lignes (10×10×10), LIMIT adapte au besoin réel.
        $this->connection->executeStatement(
            'INSERT INTO utilisateurinformations (
                NOM_UTILISATEURINFORMATIONS, PRENOM_UTILISATEURINFORMATIONS,
                TELFIXE_UTILISATEURINFORMATIONS, TELPORTABLE_UTILISATEURINFORMATIONS,
                TELFAX_UTILISATEURINFORMATIONS, MAIL_UTILISATEURINFORMATIONS,
                SOCIETE_UTILISATEURINFORMATIONS, NUMEROADRESSE_UTILISATEURINFORMATIONS,
                RUEADRESSE_UTILISATEURINFORMATIONS, CPADRESSE_UTILISATEURINFORMATIONS,
                VILLEADRESSE_UTILISATEURINFORMATIONS, WEB_UTILISATEURINFORMATIONS,
                OBS_UTILISATEURINFORMATIONS, ID_UTILISATEURCIVILITE,
                ID_FONCTION, GRADE_UTILISATEURINFORMATIONS
            )
            SELECT NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL
            FROM (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                  UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10) a
            CROSS JOIN (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                        UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10) b
            CROSS JOIN (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                        UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10) c
            LIMIT '.$count
        );

        // Affecte un ID unique (les IDs fraîchement insérés) à chaque commune.
        // Les IDs assignés vont de ($maxId + 1) à ($maxId + $count) inclus.
        foreach ($communes as $i => $commune) {
            $this->connection->executeStatement(
                'UPDATE adressecommune SET ID_UTILISATEURINFORMATIONS = ? WHERE NUMINSEE_COMMUNE = ?',
                [$maxId + $i + 1, $commune['NUMINSEE_COMMUNE']]
            );
        }

        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Nullifie les doublons et les références orphelines dans groupement.ID_UTILISATEURINFORMATIONS.
     * La colonne est BIGINT DEFAULT NULL dès la création de la table (Version20250211103328),
     * ce qui permet d'utiliser NULL plutôt que de créer de fausses lignes dans utilisateurinformations.
     *
     * Stratégie doublons : conserve MIN(ID_GROUPEMENT) par groupe, met les autres à NULL.
     * Stratégie orphelins : toute valeur inexistante dans utilisateurinformations passe à NULL.
     */
    private function fixGroupementUnicite(): void
    {
        // Doublons : garde le premier groupement (MIN), met les autres à NULL
        $this->connection->executeStatement(
            'UPDATE groupement g
             INNER JOIN (
                 SELECT ID_UTILISATEURINFORMATIONS, MIN(ID_GROUPEMENT) AS min_id
                 FROM groupement
                 WHERE ID_UTILISATEURINFORMATIONS IS NOT NULL
                 GROUP BY ID_UTILISATEURINFORMATIONS
                 HAVING COUNT(*) > 1
             ) dupes ON g.ID_UTILISATEURINFORMATIONS = dupes.ID_UTILISATEURINFORMATIONS
             SET g.ID_UTILISATEURINFORMATIONS = NULL
             WHERE g.ID_GROUPEMENT != dupes.min_id'
        );

        // Orphelins : références vers des utilisateurinformations inexistants
        $this->connection->executeStatement(
            'UPDATE groupement
             SET ID_UTILISATEURINFORMATIONS = NULL
             WHERE ID_UTILISATEURINFORMATIONS IS NOT NULL
             AND ID_UTILISATEURINFORMATIONS NOT IN (
                 SELECT ID_UTILISATEURINFORMATIONS FROM utilisateurinformations
             )'
        );
    }

    /**
     * Supprime tout index mal nommé sur groupement.ID_UTILISATEURINFORMATIONS
     * et crée l'index nommé "ID_UTILISATEURINFORMATIONS" attendu par Version20250305163248.
     *
     * Ignoré si UNIQ_C8C9F152B2A0DA51 existe déjà (Version20250305163248 a déjà tourné).
     *
     * Note : SET FOREIGN_KEY_CHECKS = 0 ne contourne PAS l'erreur 1553 sur DROP INDEX.
     * La FK doit être supprimée explicitement avant de pouvoir supprimer l'index.
     * Version20250305163248 recrée la FK sous le nom FK_C8C9F152B2A0DA51.
     */
    private function fixGroupementIndexName(): void
    {
        // Si la migration cible a déjà tourné, l'index s'appelle UNIQ_C8C9F152B2A0DA51 : rien à faire
        $afterMigrationIndexExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*)
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'groupement'
             AND INDEX_NAME = 'UNIQ_C8C9F152B2A0DA51'"
        );

        if ($afterMigrationIndexExists > 0) {
            return;
        }

        // Supprime la FK qui bloque la suppression de l'index (erreur MySQL 1553).
        // Version20250305163248 recrée une FK équivalente (FK_C8C9F152B2A0DA51).
        $fkName = $this->connection->fetchOne(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'groupement'
             AND COLUMN_NAME = 'ID_UTILISATEURINFORMATIONS'
             AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1"
        );

        if (false !== $fkName && null !== $fkName) {
            $this->connection->executeStatement(sprintf(
                'ALTER TABLE `groupement` DROP FOREIGN KEY `%s`',
                $fkName
            ));
        }

        // Supprime l'index existant s'il porte un nom inattendu
        $wrongIndexName = $this->connection->fetchOne(
            "SELECT INDEX_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'groupement'
             AND COLUMN_NAME = 'ID_UTILISATEURINFORMATIONS'
             AND INDEX_NAME NOT IN ('PRIMARY', 'ID_UTILISATEURINFORMATIONS')
             LIMIT 1"
        );

        if (false !== $wrongIndexName && null !== $wrongIndexName) {
            $this->connection->executeStatement(sprintf(
                'DROP INDEX `%s` ON `groupement`',
                $wrongIndexName
            ));
        }

        // Crée l'index avec le nom exact qu'attend la migration Version20250305163248
        $expectedExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*)
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'groupement'
             AND INDEX_NAME = 'ID_UTILISATEURINFORMATIONS'"
        );

        if (0 === $expectedExists) {
            $this->connection->executeStatement(
                'CREATE INDEX `ID_UTILISATEURINFORMATIONS` ON `groupement` (`ID_UTILISATEURINFORMATIONS`)'
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Les lignes insérées dans utilisateurinformations ne sont pas réversibles de manière sûre.
        // L'index sur groupement est restauré par le down() de Version20250305163248.
    }
}
