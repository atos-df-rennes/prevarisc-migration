<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250328101720 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modifications des clés adresses et commissions';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf($schema->getTable('adresserue')->hasForeignKey('fk_ec0264246d48241f'), "Clés adresses et commissions déjà modifiées.");

        $this->addSql('ALTER TABLE adresserue DROP FOREIGN KEY fk_adresserue_adressecommune1');
        $this->addSql('ALTER TABLE adresserue DROP FOREIGN KEY fk_adresserue_adresseruetype');
        $this->addSql('ALTER TABLE adresserue CHANGE LIBELLE_RUE LIBELLE_RUE VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE adresserue ADD CONSTRAINT FK_EC0264246D48241F FOREIGN KEY (ID_RUETYPE) REFERENCES adresseruetype (ID_RUETYPE)');
        $this->addSql('ALTER TABLE adresserue ADD CONSTRAINT FK_EC0264248B5749D3 FOREIGN KEY (NUMINSEE_COMMUNE) REFERENCES adressecommune (NUMINSEE_COMMUNE)');
        $this->addSql('ALTER TABLE adresseruetype CHANGE LIBELLE_RUETYPE LIBELLE_RUETYPE VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE categorie CHANGE LIBELLE_CATEGORIE LIBELLE_CATEGORIE VARCHAR(15) NOT NULL');
        $this->addSql('ALTER TABLE commissionregle DROP FOREIGN KEY fk_commissionregle_adressecommune1');
        $this->addSql('ALTER TABLE commissionregle DROP FOREIGN KEY fk_commissionregle_commission1');
        $this->addSql('ALTER TABLE commissionregle CHANGE ID_GROUPEMENT ID_GROUPEMENT BIGINT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE commissionregle ADD CONSTRAINT FK_E87498A3CD93FCB7 FOREIGN KEY (ID_GROUPEMENT) REFERENCES groupement (ID_GROUPEMENT)');
        $this->addSql('ALTER TABLE commissionregle ADD CONSTRAINT FK_E87498A3193F0CBD FOREIGN KEY (ID_COMMISSION) REFERENCES commission (ID_COMMISSION) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commissionregle ADD CONSTRAINT FK_E87498A38B5749D3 FOREIGN KEY (NUMINSEE_COMMUNE) REFERENCES adressecommune (NUMINSEE_COMMUNE) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf($schema->getTable('adresserue')->hasForeignKey('fk_adresserue_adressecommune1'), "Clés adresses et commissions déjà annulées.");

        $this->addSql('ALTER TABLE adresserue DROP FOREIGN KEY FK_EC0264246D48241F');
        $this->addSql('ALTER TABLE adresserue DROP FOREIGN KEY FK_EC0264248B5749D3');
        $this->addSql('ALTER TABLE adresserue CHANGE LIBELLE_RUE LIBELLE_RUE VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci` COMMENT \'Pour le moment, le nom du type de la rue doit être cité dans le nom de la rue. (RUE DES ACACIAS)\'');
        $this->addSql('ALTER TABLE adresserue ADD CONSTRAINT fk_adresserue_adressecommune1 FOREIGN KEY (NUMINSEE_COMMUNE) REFERENCES adressecommune (NUMINSEE_COMMUNE) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE adresserue ADD CONSTRAINT fk_adresserue_adresseruetype FOREIGN KEY (ID_RUETYPE) REFERENCES adresseruetype (ID_RUETYPE) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE adresseruetype CHANGE LIBELLE_RUETYPE LIBELLE_RUETYPE VARCHAR(32) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci` COMMENT \'Exemple : BOULEVARD\'');
        $this->addSql('ALTER TABLE categorie CHANGE LIBELLE_CATEGORIE LIBELLE_CATEGORIE VARCHAR(15) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE commissionregle DROP FOREIGN KEY FK_E87498A3CD93FCB7');
        $this->addSql('ALTER TABLE commissionregle DROP FOREIGN KEY FK_E87498A3193F0CBD');
        $this->addSql('ALTER TABLE commissionregle DROP FOREIGN KEY FK_E87498A38B5749D3');
        $this->addSql('ALTER TABLE commissionregle CHANGE ID_GROUPEMENT ID_GROUPEMENT BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE commissionregle ADD CONSTRAINT fk_commissionregle_adressecommune1 FOREIGN KEY (NUMINSEE_COMMUNE) REFERENCES adressecommune (NUMINSEE_COMMUNE) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commissionregle ADD CONSTRAINT fk_commissionregle_commission1 FOREIGN KEY (ID_COMMISSION) REFERENCES commission (ID_COMMISSION) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT fk_dossier_avis1 FOREIGN KEY (AVIS_DOSSIER) REFERENCES avis (ID_AVIS) ON UPDATE CASCADE ON DELETE NO ACTION');
    }
}
