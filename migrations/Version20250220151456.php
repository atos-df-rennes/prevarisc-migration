<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220151456 extends AbstractMigration
{
    public function getDescription() : string
    {
        return "Statut des pièces Plat'AU dans Prevarisc.";
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("CREATE TABLE IF NOT EXISTS `piecejointestatut` (
            `ID_PIECEJOINTESTATUT` int(20) NOT NULL AUTO_INCREMENT,
            `NOM_STATUT` varchar(50),
            PRIMARY KEY (`ID_PIECEJOINTESTATUT`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $this->addSql("INSERT IGNORE INTO `piecejointestatut`(`ID_PIECEJOINTESTATUT`,`NOM_STATUT`) VALUES
            (1,'not_exported'),
            (2,'to_be_exported'),
            (3,'exported'),
            (4,'on_error')
        ");

        $this->skipIf($schema->getTable('piecejointe')->hasColumn('ID_PIECEJOINTESTATUT'), "Statut des pièces déjà existant.");

        $this->addSql("ALTER TABLE `piecejointe` ADD COLUMN `ID_PIECEJOINTESTATUT` int(20) DEFAULT 1");
        $this->addSql("ALTER TABLE `piecejointe` ADD CONSTRAINT `fk_piecejointe_piecejointestatut1` FOREIGN KEY (`ID_PIECEJOINTESTATUT`) REFERENCES `piecejointestatut` (`ID_PIECEJOINTESTATUT`) ON DELETE SET NULL ON UPDATE CASCADE");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DROP TABLE IF EXISTS `piecejointestatut`");

        $this->skipIf(!$schema->getTable('piecejointe')->hasColumn('ID_PIECEJOINTESTATUT'), "Statut des pièces non existant.");

        $this->addSql("ALTER TABLE `piecejointe` DROP FOREIGN KEY `fk_piecejointe_piecejointestatut1`");
        $this->addSql("ALTER TABLE `piecejointe` DROP COLUMN `ID_PIECEJOINTESTATUT`");
    }
}
