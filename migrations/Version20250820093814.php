<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250820093814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Correction du schéma';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->skipIf($schema->getTable('avisderogations')->getColumn('DISPLAY_HISTORIQUE')->getNotnull(), "Correction du schéma déjà effectuée.");
        $this->addSql('ALTER TABLE adresseruetype CHANGE ABREVIATION_RUETYPE ABREVIATION_RUETYPE VARCHAR(16) NOT NULL');
        $this->addSql('ALTER TABLE avis CHANGE LIBELLE_AVIS LIBELLE_AVIS VARCHAR(30) NOT NULL');
        $this->addSql('ALTER TABLE avisderogations CHANGE DISPLAY_HISTORIQUE DISPLAY_HISTORIQUE TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE genre CHANGE LIBELLE_GENRE LIBELLE_GENRE VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE periodicite CHANGE LOCALSOMMEIL_PERIODICITE LOCALSOMMEIL_PERIODICITE TINYINT(1) NOT NULL, CHANGE PERIODICITE_PERIODICITE PERIODICITE_PERIODICITE INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE prescriptiontexteliste CHANGE VISIBLE_TEXTE VISIBLE_TEXTE TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE textesappl CHANGE NUM_TEXTESAPPL NUM_TEXTESAPPL INT NOT NULL');
        $this->addSql('ALTER TABLE typetextesappl CHANGE LIBELLE_TYPETEXTEAPPL LIBELLE_TYPETEXTEAPPL LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf(!$schema->getTable('avisderogations')->getColumn('DISPLAY_HISTORIQUE')->getNotnull(), "Correction du schéma déjà annulée.");

        $this->addSql('ALTER TABLE adresseruetype CHANGE ABREVIATION_RUETYPE ABREVIATION_RUETYPE VARCHAR(16) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci` COMMENT \'Exemple : BLVD\'');
        $this->addSql('ALTER TABLE avis CHANGE LIBELLE_AVIS LIBELLE_AVIS VARCHAR(30) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE avisderogations CHANGE DISPLAY_HISTORIQUE DISPLAY_HISTORIQUE TINYINT(1) DEFAULT \'0\'');
        $this->addSql('ALTER TABLE genre CHANGE LIBELLE_GENRE LIBELLE_GENRE VARCHAR(50) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE periodicite CHANGE LOCALSOMMEIL_PERIODICITE LOCALSOMMEIL_PERIODICITE TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE PERIODICITE_PERIODICITE PERIODICITE_PERIODICITE INT UNSIGNED DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE prescriptiontexteliste CHANGE VISIBLE_TEXTE VISIBLE_TEXTE TINYINT(1) DEFAULT \'1\'');
        $this->addSql('ALTER TABLE textesappl CHANGE NUM_TEXTESAPPL NUM_TEXTESAPPL INT DEFAULT 99999 NOT NULL');
        $this->addSql('ALTER TABLE typetextesappl CHANGE LIBELLE_TYPETEXTEAPPL LIBELLE_TYPETEXTEAPPL TEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE utilisateurpreferences CHANGE dashboard_blocs blocs_dashboard VARCHAR(500) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`');
    }
}
