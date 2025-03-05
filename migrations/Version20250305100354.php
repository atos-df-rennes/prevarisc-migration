<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250305100354 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE commission DROP FOREIGN KEY fk_commission_commissiontype1');
        $this->addSql('ALTER TABLE commission CHANGE LIBELLE_COMMISSION LIBELLE_COMMISSION VARCHAR(80) NOT NULL');
        $this->addSql('ALTER TABLE commission ADD CONSTRAINT FK_1C65015860FC5742 FOREIGN KEY (ID_COMMISSIONTYPE) REFERENCES commissiontype (ID_COMMISSIONTYPE)');
        $this->addSql('CREATE INDEX IDX_1C65015860FC5742 ON commission (ID_COMMISSIONTYPE)');
        $this->addSql('ALTER TABLE commissiontype CHANGE LIBELLE_COMMISSIONTYPE LIBELLE_COMMISSIONTYPE VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE groupement DROP INDEX ID_UTILISATEURINFORMATIONS, ADD UNIQUE INDEX UNIQ_C8C9F152B2A0DA51 (ID_UTILISATEURINFORMATIONS)');
        $this->addSql('ALTER TABLE groupement DROP FOREIGN KEY fk_groupement_groupementtype1');
        $this->addSql('ALTER TABLE groupement CHANGE ID_UTILISATEURINFORMATIONS ID_UTILISATEURINFORMATIONS BIGINT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE groupement ADD CONSTRAINT FK_C8C9F152B2A0DA51 FOREIGN KEY (ID_UTILISATEURINFORMATIONS) REFERENCES utilisateurinformations (ID_UTILISATEURINFORMATIONS)');
        $this->addSql('ALTER TABLE groupement ADD CONSTRAINT FK_C8C9F152398921DE FOREIGN KEY (ID_GROUPEMENTTYPE) REFERENCES groupementtype (ID_GROUPEMENTTYPE)');
        $this->addSql('CREATE INDEX IDX_C8C9F152398921DE ON groupement (ID_GROUPEMENTTYPE)');
        $this->addSql('CREATE INDEX IDX_1D1C63B38B5749D3 ON utilisateur (NUMINSEE_COMMUNE)');
        $this->addSql('ALTER TABLE utilisateurcommission DROP FOREIGN KEY fk_utilisateurcommission_commission1');
        $this->addSql('ALTER TABLE utilisateurcommission DROP FOREIGN KEY fk_utilisateurcommission_utilisateur1');
        $this->addSql('ALTER TABLE utilisateurcommission ADD CONSTRAINT FK_31575569F6170F81 FOREIGN KEY (ID_UTILISATEUR) REFERENCES utilisateur (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE utilisateurcommission ADD CONSTRAINT FK_31575569193F0CBD FOREIGN KEY (ID_COMMISSION) REFERENCES commission (ID_COMMISSION)');
        $this->addSql('DROP INDEX fk_utilisateurcommission_utilisateur1_idx ON utilisateurcommission');
        $this->addSql('CREATE INDEX IDX_31575569F6170F81 ON utilisateurcommission (ID_UTILISATEUR)');
        $this->addSql('CREATE INDEX IDX_31575569193F0CBD ON utilisateurcommission (ID_COMMISSION)');
        $this->addSql('ALTER TABLE utilisateurgroupement DROP FOREIGN KEY fk_utilisateurgroupement_groupement1');
        $this->addSql('ALTER TABLE utilisateurgroupement DROP FOREIGN KEY fk_utilisateurgroupement_utilisateur1');
        $this->addSql('ALTER TABLE utilisateurgroupement ADD CONSTRAINT FK_E5FBA563F6170F81 FOREIGN KEY (ID_UTILISATEUR) REFERENCES utilisateur (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE utilisateurgroupement ADD CONSTRAINT FK_E5FBA563CD93FCB7 FOREIGN KEY (ID_GROUPEMENT) REFERENCES groupement (ID_GROUPEMENT)');
        $this->addSql('DROP INDEX fk_utilisateurgroupement_utilisateur1_idx ON utilisateurgroupement');
        $this->addSql('CREATE INDEX IDX_E5FBA563F6170F81 ON utilisateurgroupement (ID_UTILISATEUR)');
        $this->addSql('CREATE INDEX IDX_E5FBA563CD93FCB7 ON utilisateurgroupement (ID_GROUPEMENT)');
        $this->addSql('ALTER TABLE utilisateur ADD sso TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE commission DROP FOREIGN KEY FK_1C65015860FC5742');
        $this->addSql('ALTER TABLE commission CHANGE LIBELLE_COMMISSION LIBELLE_COMMISSION VARCHAR(50) CHARACTER SET utf8 DEFAULT \'Nom de la commission\' NOT NULL COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE commission ADD CONSTRAINT fk_commission_commissiontype1 FOREIGN KEY (ID_COMMISSIONTYPE) REFERENCES commissiontype (ID_COMMISSIONTYPE) ON UPDATE CASCADE ON DELETE NO ACTION');
        $this->addSql('DROP INDEX idx_1c65015860fc5742 ON commission');
        $this->addSql('ALTER TABLE commission ADD CONSTRAINT FK_1C65015860FC5742 FOREIGN KEY (ID_COMMISSIONTYPE) REFERENCES commissiontype (ID_COMMISSIONTYPE)');
        $this->addSql('ALTER TABLE commissiontype CHANGE LIBELLE_COMMISSIONTYPE LIBELLE_COMMISSIONTYPE VARCHAR(50) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE groupement DROP INDEX UNIQ_C8C9F152B2A0DA51, ADD INDEX ID_UTILISATEURINFORMATIONS (ID_UTILISATEURINFORMATIONS)');
        $this->addSql('ALTER TABLE groupement DROP FOREIGN KEY FK_C8C9F152B2A0DA51');
        $this->addSql('ALTER TABLE groupement DROP FOREIGN KEY FK_C8C9F152398921DE');
        $this->addSql('ALTER TABLE groupement CHANGE ID_UTILISATEURINFORMATIONS ID_UTILISATEURINFORMATIONS BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE groupement ADD CONSTRAINT fk_groupement_groupementtype1 FOREIGN KEY (ID_GROUPEMENTTYPE) REFERENCES groupementtype (ID_GROUPEMENTTYPE) ON UPDATE CASCADE ON DELETE NO ACTION');
        $this->addSql('DROP INDEX idx_c8c9f152398921de ON groupement');
        $this->addSql('DROP INDEX IDX_1D1C63B38B5749D3 ON utilisateur');
        $this->addSql('ALTER TABLE utilisateurcommission DROP FOREIGN KEY FK_31575569F6170F81');
        $this->addSql('ALTER TABLE utilisateurcommission DROP FOREIGN KEY FK_31575569193F0CBD');
        $this->addSql('ALTER TABLE utilisateurcommission ADD CONSTRAINT fk_utilisateurcommission_commission1 FOREIGN KEY (ID_COMMISSION) REFERENCES commission (ID_COMMISSION) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateurcommission ADD CONSTRAINT fk_utilisateurcommission_utilisateur1 FOREIGN KEY (ID_UTILISATEUR) REFERENCES utilisateur (ID_UTILISATEUR) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_31575569193f0cbd ON utilisateurcommission');
        $this->addSql('DROP INDEX idx_31575569f6170f81 ON utilisateurcommission');
        $this->addSql('CREATE INDEX fk_utilisateurcommission_utilisateur1_idx ON utilisateurcommission (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE utilisateurgroupement DROP FOREIGN KEY FK_E5FBA563F6170F81');
        $this->addSql('ALTER TABLE utilisateurgroupement DROP FOREIGN KEY FK_E5FBA563CD93FCB7');
        $this->addSql('ALTER TABLE utilisateurgroupement ADD CONSTRAINT fk_utilisateurgroupement_groupement1 FOREIGN KEY (ID_GROUPEMENT) REFERENCES groupement (ID_GROUPEMENT) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateurgroupement ADD CONSTRAINT fk_utilisateurgroupement_utilisateur1 FOREIGN KEY (ID_UTILISATEUR) REFERENCES utilisateur (ID_UTILISATEUR) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_e5fba563cd93fcb7 ON utilisateurgroupement');
        $this->addSql('DROP INDEX idx_e5fba563f6170f81 ON utilisateurgroupement');
        $this->addSql('CREATE INDEX fk_utilisateurgroupement_utilisateur1_idx ON utilisateurgroupement (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE utilisateur DROP sso');
    }
}
