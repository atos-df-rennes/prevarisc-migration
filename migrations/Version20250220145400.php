<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220145400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppression de tables non utiles.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE IF EXISTS `effectifdegagement`');
        $this->addSql('DROP TABLE IF EXISTS `dossiereffectifdegagement`');
        $this->addSql('DROP TABLE IF EXISTS `etablissementeffectifdegagement`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS `effectifdegagement` (
            ID_EFFECTIF_DEGAGEMENT bigint PRIMARY KEY NOT NULL AUTO_INCREMENT,
            DESCRIPTION_EFFECTIF TEXT,
            DESCRIPTION_DEGAGEMENT TEXT
        )');
        $this->addSql('CREATE TABLE `dossiereffectifdegagement` (
            ID_DOSSIER_EFFECTIF_DEGAGEMENT bigint PRIMARY KEY NOT NULL AUTO_INCREMENT,
            ID_EFFECTIF_DEGAGEMENT bigint,
            ID_DOSSIER bigint,
            FOREIGN KEY(`ID_EFFECTIF_DEGAGEMENT`) REFERENCES `effectifdegagement`(`ID_EFFECTIF_DEGAGEMENT`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY(`ID_DOSSIER`) REFERENCES `dossier`(`ID_DOSSIER`) ON DELETE CASCADE ON UPDATE NO ACTION
        )');
        $this->addSql('CREATE TABLE `etablissementeffectifdegagement` (
            ID_ETABLISSEMENT_EFFECTIF_DEGAGEMENT bigint PRIMARY KEY NOT NULL AUTO_INCREMENT, 
            ID_EFFECTIF_DEGAGEMENT bigint,
            ID_ETABLISSEMENT bigint unsigned,
            FOREIGN KEY(`ID_EFFECTIF_DEGAGEMENT`) REFERENCES `effectifdegagement`(`ID_EFFECTIF_DEGAGEMENT`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY(`ID_ETABLISSEMENT`) REFERENCES `etablissement`(`ID_ETABLISSEMENT`) ON DELETE CASCADE ON UPDATE NO ACTION
        )');
    }
}
