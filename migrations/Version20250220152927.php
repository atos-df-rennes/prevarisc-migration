<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220152927 extends AbstractMigration
{
    public function getDescription() : string
    {
        return "Synchronisation des statuts des pièces Plat'AU.";
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('piecejointe')->hasColumn('ID_PLATAU'), "Synchronisation des statuts des pièces Plat'AU déjà existante.");

        $this->addSql("ALTER TABLE `piecejointe` ADD COLUMN `ID_PLATAU` CHAR(11) NULL DEFAULT NULL");
        $this->addSql("ALTER TABLE `piecejointe` ADD COLUMN `MESSAGE_ERREUR` VARCHAR(255) NULL DEFAULT NULL");

        $this->addSql("ALTER TABLE `piecejointestatut` ADD COLUMN `NOM_LISIBLE` VARCHAR(50) NOT NULL");
        $this->addSql("INSERT IGNORE INTO `piecejointestatut` VALUES (5, 'awaiting_status', 'Vérification du statut')");
        $this->addSql("UPDATE `piecejointestatut` pjs JOIN (
            SELECT 1 as ID_PIECEJOINTESTATUT, 'Non envoyé' as new_name
            UNION ALL
            SELECT 2, 'En attente d''envoi'
            UNION ALL
            SELECT 3, 'Envoyé'
            UNION ALL
            SELECT 4, 'Erreur lors de l''envoi'
        ) vals ON pjs.ID_PIECEJOINTESTATUT = vals.ID_PIECEJOINTESTATUT
        SET `NOM_LISIBLE` = new_name");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('piecejointe')->hasColumn('ID_PLATAU'), "Synchronisation des statuts des pièces Plat'AU non existante.");

        $this->addSql("ALTER TABLE `piecejointe` DROP COLUMN `ID_PLATAU`");
        $this->addSql("ALTER TABLE `piecejointe` DROP COLUMN `MESSAGE_ERREUR`");
        $this->addSql("ALTER TABLE `piecejointestatut` DROP COLUMN `NOM_LISIBLE`");
    }
}
