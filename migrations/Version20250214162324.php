<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250214162324 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE utilisateur DROP INDEX fk_utilisateur_utilisateurinformations1_idx, ADD UNIQUE INDEX UNIQ_1D1C63B3B2A0DA51 (ID_UTILISATEURINFORMATIONS)');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY fk_utilisateur_groupe1');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY fk_utilisateur_utilisateurinformations1');
        $this->addSql('ALTER TABLE utilisateur ADD roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', CHANGE ACTIF_UTILISATEUR ACTIF_UTILISATEUR TINYINT(1) NOT NULL, CHANGE FAILED_LOGIN_ATTEMPTS_UTILISATEUR FAILED_LOGIN_ATTEMPTS_UTILISATEUR SMALLINT NOT NULL, CHANGE DERNIERE_VISITE_DASHBOARD DERNIERE_VISITE_DASHBOARD DATETIME NOT NULL, CHANGE DERNIERE_VISITE_PIECES_DOSSIER DERNIERE_VISITE_PIECES_DOSSIER DATETIME NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3A9F11EBF FOREIGN KEY (ID_GROUPE) REFERENCES groupe (ID_GROUPE)');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3B2A0DA51 FOREIGN KEY (ID_UTILISATEURINFORMATIONS) REFERENCES utilisateurinformations (ID_UTILISATEURINFORMATIONS)');
        $this->addSql('CREATE INDEX IDX_1D1C63B3A9F11EBF ON utilisateur (ID_GROUPE)');
        $this->addSql('ALTER TABLE utilisateurinformations DROP FOREIGN KEY fk_utilisateurinformations_fonction1');
        $this->addSql('ALTER TABLE utilisateurinformations DROP FOREIGN KEY fk_utilisateurinformations_utilisateurcivilite1');
        $this->addSql('ALTER TABLE utilisateurinformations ADD CONSTRAINT FK_C5CDDBE45A01E5E9 FOREIGN KEY (ID_UTILISATEURCIVILITE) REFERENCES utilisateurcivilite (ID_UTILISATEURCIVILITE)');
        $this->addSql('ALTER TABLE utilisateurinformations ADD CONSTRAINT FK_C5CDDBE456292963 FOREIGN KEY (ID_FONCTION) REFERENCES fonction (ID_FONCTION)');
        $this->addSql('CREATE INDEX IDX_C5CDDBE45A01E5E9 ON utilisateurinformations (ID_UTILISATEURCIVILITE)');
        $this->addSql('CREATE INDEX IDX_C5CDDBE456292963 ON utilisateurinformations (ID_FONCTION)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE utilisateur DROP INDEX UNIQ_1D1C63B3B2A0DA51, ADD INDEX fk_utilisateur_utilisateurinformations1_idx (ID_UTILISATEURINFORMATIONS)');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3A9F11EBF');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3B2A0DA51');
        $this->addSql('ALTER TABLE utilisateur DROP roles, CHANGE PASSWD_UTILISATEUR PASSWD_UTILISATEUR VARCHAR(32) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, CHANGE ACTIF_UTILISATEUR ACTIF_UTILISATEUR TINYINT(1) DEFAULT \'1\' NOT NULL, CHANGE FAILED_LOGIN_ATTEMPTS_UTILISATEUR FAILED_LOGIN_ATTEMPTS_UTILISATEUR TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE DERNIERE_VISITE_DASHBOARD DERNIERE_VISITE_DASHBOARD DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE DERNIERE_VISITE_PIECES_DOSSIER DERNIERE_VISITE_PIECES_DOSSIER DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT fk_utilisateur_groupe1 FOREIGN KEY (ID_GROUPE) REFERENCES groupe (ID_GROUPE) ON UPDATE CASCADE ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT fk_utilisateur_utilisateurinformations1 FOREIGN KEY (ID_UTILISATEURINFORMATIONS) REFERENCES utilisateurinformations (ID_UTILISATEURINFORMATIONS) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_1d1c63b3a9f11ebf ON utilisateur');
        $this->addSql('ALTER TABLE utilisateurinformations DROP FOREIGN KEY FK_C5CDDBE45A01E5E9');
        $this->addSql('ALTER TABLE utilisateurinformations DROP FOREIGN KEY FK_C5CDDBE456292963');
        $this->addSql('ALTER TABLE utilisateurinformations ADD CONSTRAINT fk_utilisateurinformations_fonction1 FOREIGN KEY (ID_FONCTION) REFERENCES fonction (ID_FONCTION) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('ALTER TABLE utilisateurinformations ADD CONSTRAINT fk_utilisateurinformations_utilisateurcivilite1 FOREIGN KEY (ID_UTILISATEURCIVILITE) REFERENCES utilisateurcivilite (ID_UTILISATEURCIVILITE) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('DROP INDEX idx_c5cddbe45a01e5e9 ON utilisateurinformations');
        $this->addSql('DROP INDEX idx_c5cddbe456292963 ON utilisateurinformations');
    }
}
