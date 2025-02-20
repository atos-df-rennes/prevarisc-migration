<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220144606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Avis & dérogations et Effectifs & dégagements.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS `avisderogations` (
            `ID_AVIS_DEROGATION` BIGINT(20) NOT NULL AUTO_INCREMENT,
            `TYPE` VARCHAR(50) NOT NULL,
            `TITRE` VARCHAR(50) NOT NULL,
            `INFORMATIONS` TEXT DEFAULT NULL,
            `AVIS` INT(1) UNSIGNED DEFAULT NULL,
            `ID_DOSSIER_LIE` BIGINT(20) DEFAULT NULL,
            `DISPLAY_HISTORIQUE` TINYINT DEFAULT 0,
            `ID_DOSSIER` BIGINT(20) NOT NULL,
            PRIMARY KEY (`ID_AVIS_DEROGATION`),
            KEY `fk_avisderogation_avis1_idx` (`AVIS`),
            KEY `fk_avisderogation_dossierlie1_idx` (`ID_DOSSIER_LIE`),
            KEY `fk_avisderogation_dossier1_idx` (`ID_DOSSIER`),
            CONSTRAINT `fk_avisderogation_avis1` FOREIGN KEY (`AVIS`) REFERENCES `avis` (`ID_AVIS`) ON DELETE NO ACTION ON UPDATE CASCADE,
            CONSTRAINT `fk_avisderogation_dossierlie1_idx` FOREIGN KEY (`ID_DOSSIER_LIE`) REFERENCES `dossier` (`ID_DOSSIER`) ON DELETE NO ACTION ON UPDATE CASCADE,
            CONSTRAINT `fk_avisderogation_dossier1_idx` FOREIGN KEY (`ID_DOSSIER`) REFERENCES `dossier` (`ID_DOSSIER`) ON DELETE CASCADE ON UPDATE CASCADE
        )');
        $this->addSql("INSERT IGNORE INTO `resources`(`id_resource`,`name`, `text`) VALUES(202,'avisderogations', 'Avis et dérogations')");
        $this->addSql("INSERT IGNORE INTO `privileges` (`id_privilege`,`name`, `text`, `id_resource`) VALUES (214,'avis_derogations', 'Lecture et écriture des avis et dérogations', 202)");
        $this->addSql("INSERT IGNORE INTO `resources`(`id_resource`,`name`, `text`) VALUES(203,'effectifs_degagements', 'Effectifs et Dégagements')");
        $this->addSql("INSERT IGNORE INTO `privileges` (`id_privilege`,`name`, `text`, `id_resource`) VALUES (215,'effectifs_degagements_ets', 'Etablissements', 203)");
        $this->addSql("INSERT IGNORE INTO `privileges` (`id_privilege`,`name`, `text`, `id_resource`) VALUES (216,'effectifs_degagements_doss', 'Dossiers', 203)");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM `privileges` WHERE `id_privilege` = 216');
        $this->addSql('DELETE FROM `privileges` WHERE `id_privilege` = 215');
        $this->addSql('DELETE FROM `resources` WHERE `id_privilege` = 203');
        $this->addSql('DELETE FROM `privileges` WHERE `id_privilege` = 214');
        $this->addSql('DELETE FROM `resources` WHERE `id_privilege` = 202');
        $this->addSql('DROP TABLE IF EXISTS `avisderogations`');
    }
}
