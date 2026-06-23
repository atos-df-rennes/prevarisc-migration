<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250318140114 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Adaptation pour la suppression d'élément.";
    }

    public function up(Schema $schema): void
    {
        $this->skipIf($schema->getTable('dossier')->hasIndex('idx_3d48e037792da484'), "Adaptation pour la suppression d'élément déjà effectuée.");

        $this->addSql('ALTER TABLE changement CHANGE LIBELLE_CHANGEMENT LIBELLE_CHANGEMENT VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX fk_commission_commissiontype1_idx ON commission');
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY fk_dossier_dossiertype1');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E0372CBDFB70 FOREIGN KEY (DELETED_BY) REFERENCES utilisateur (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E037792DA484 FOREIGN KEY (TYPE_DOSSIER) REFERENCES dossiertype (ID_DOSSIERTYPE)');
        $this->addSql('CREATE INDEX IDX_3D48E0372CBDFB70 ON dossier (DELETED_BY)');
        $this->addSql('CREATE INDEX IDX_3D48E037CD7B4421 ON dossier (DATESUPPRESSION_DOSSIER)');
        $this->addSql('CREATE INDEX IDX_3D48E037792DA484 ON dossier (TYPE_DOSSIER)');
        $this->addSql('ALTER TABLE dossiernature DROP FOREIGN KEY fk_dossiernature_dossier1');
        $this->addSql('ALTER TABLE dossiernature DROP FOREIGN KEY fk_dossiernature_dossiernatureliste1');
        $this->addSql('ALTER TABLE dossiernature ADD CONSTRAINT FK_F260747C113C362E FOREIGN KEY (ID_DOSSIER) REFERENCES dossier (ID_DOSSIER)');
        $this->addSql('ALTER TABLE dossiernature ADD CONSTRAINT FK_F260747C1C901039 FOREIGN KEY (ID_NATURE) REFERENCES dossiernatureliste (ID_DOSSIERNATURE)');
        $this->addSql('CREATE INDEX IDX_F260747C113C362E ON dossiernature (ID_DOSSIER)');
        $this->addSql('CREATE INDEX IDX_F260747C1C901039 ON dossiernature (ID_NATURE)');
        $this->addSql('ALTER TABLE etablissementdossier DROP FOREIGN KEY fk_etablissementdossier_dossier1');
        $this->addSql('ALTER TABLE etablissementdossier DROP FOREIGN KEY fk_etablissementdossier_etablissement1');
        $this->addSql('ALTER TABLE etablissementdossier ADD CONSTRAINT FK_E42D132113C362E FOREIGN KEY (ID_DOSSIER) REFERENCES dossier (ID_DOSSIER)');
        $this->addSql('ALTER TABLE etablissementdossier ADD CONSTRAINT FK_E42D1322763CF4F FOREIGN KEY (ID_ETABLISSEMENT) REFERENCES etablissement (ID_ETABLISSEMENT)');
        $this->addSql('CREATE INDEX IDX_E42D132113C362E ON etablissementdossier (ID_DOSSIER)');
        $this->addSql('CREATE INDEX IDX_E42D1322763CF4F ON etablissementdossier (ID_ETABLISSEMENT)');
        $this->addSql('ALTER TABLE dossiernatureliste DROP FOREIGN KEY fk_dossiernatureliste_dossiertype1');
        $this->addSql('ALTER TABLE dossiernatureliste ADD CONSTRAINT FK_4AD058EEC303A2BF FOREIGN KEY (ID_DOSSIERTYPE) REFERENCES dossiertype (ID_DOSSIERTYPE)');
        $this->addSql('CREATE INDEX IDX_4AD058EEC303A2BF ON dossiernatureliste (ID_DOSSIERTYPE)');
        $this->addSql('ALTER TABLE etablissement ADD CONSTRAINT FK_20FD592C2CBDFB70 FOREIGN KEY (DELETED_BY) REFERENCES utilisateur (ID_UTILISATEUR)');
        $this->addSql('CREATE INDEX IDX_20FD592C2CBDFB70 ON etablissement (DELETED_BY)');
        $this->addSql('CREATE INDEX IDX_20FD592CD77762E5 ON etablissement (DATESUPPRESSION_ETABLISSEMENT)');
        $this->addSql('ALTER TABLE etablissementadresse DROP FOREIGN KEY fk_etablissementadresse_adressecommune1');
        $this->addSql('ALTER TABLE etablissementadresse DROP FOREIGN KEY fk_etablissementadresse_etablissement1');
        $this->addSql('ALTER TABLE etablissementadresse ADD CONSTRAINT FK_F05539132763CF4F FOREIGN KEY (ID_ETABLISSEMENT) REFERENCES etablissement (ID_ETABLISSEMENT)');
        $this->addSql('ALTER TABLE etablissementadresse ADD CONSTRAINT FK_F05539138B5749D3 FOREIGN KEY (NUMINSEE_COMMUNE) REFERENCES adressecommune (NUMINSEE_COMMUNE)');
        $this->addSql('CREATE INDEX IDX_F05539138B5749D3 ON etablissementadresse (NUMINSEE_COMMUNE)');
        $this->addSql('ALTER TABLE etablissementinformations DROP FOREIGN KEY fk_etablissementinformations_etablissement1');
        $this->addSql('ALTER TABLE etablissementinformations ADD CONSTRAINT FK_161D94DB2763CF4F FOREIGN KEY (ID_ETABLISSEMENT) REFERENCES etablissement (ID_ETABLISSEMENT)');
        $this->addSql('CREATE INDEX IDX_161D94DB2763CF4F ON etablissementinformations (ID_ETABLISSEMENT)');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf($schema->getTable('commission')->hasIndex('fk_commission_commissiontype1_idx'), "Adaptation pour la suppression d'élément déjà annulée.");

        $this->addSql('ALTER TABLE changement CHANGE LIBELLE_CHANGEMENT LIBELLE_CHANGEMENT VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`');
        $this->addSql('CREATE INDEX fk_commission_commissiontype1_idx ON commission (ID_COMMISSIONTYPE)');
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E0372CBDFB70');
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E037792DA484');
        $this->addSql('DROP INDEX IDX_3D48E0372CBDFB70 ON dossier');
        $this->addSql('DROP INDEX IDX_3D48E037CD7B4421 ON dossier');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT fk_dossier_dossiertype1 FOREIGN KEY (TYPE_DOSSIER) REFERENCES dossiertype (ID_DOSSIERTYPE) ON UPDATE CASCADE ON DELETE NO ACTION');
        $this->addSql('DROP INDEX idx_3d48e037792da484 ON dossier');
        $this->addSql('ALTER TABLE dossiernature DROP FOREIGN KEY FK_F260747C113C362E');
        $this->addSql('ALTER TABLE dossiernature DROP FOREIGN KEY FK_F260747C1C901039');
        $this->addSql('ALTER TABLE dossiernature ADD CONSTRAINT fk_dossiernature_dossier1 FOREIGN KEY (ID_DOSSIER) REFERENCES dossier (ID_DOSSIER) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dossiernature ADD CONSTRAINT fk_dossiernature_dossiernatureliste1 FOREIGN KEY (ID_NATURE) REFERENCES dossiernatureliste (ID_DOSSIERNATURE) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_f260747c113c362e ON dossiernature');
        $this->addSql('DROP INDEX idx_f260747c1c901039 ON dossiernature');
        $this->addSql('ALTER TABLE dossiernatureliste DROP FOREIGN KEY FK_4AD058EEC303A2BF');
        $this->addSql('ALTER TABLE dossiernatureliste ADD CONSTRAINT fk_dossiernatureliste_dossiertype1 FOREIGN KEY (ID_DOSSIERTYPE) REFERENCES dossiertype (ID_DOSSIERTYPE) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_4ad058eec303a2bf ON dossiernatureliste');
        $this->addSql('ALTER TABLE etablissement DROP FOREIGN KEY FK_20FD592C2CBDFB70');
        $this->addSql('DROP INDEX IDX_20FD592C2CBDFB70 ON etablissement');
        $this->addSql('DROP INDEX IDX_20FD592CD77762E5 ON etablissement');
        $this->addSql('ALTER TABLE etablissementadresse DROP FOREIGN KEY FK_F05539132763CF4F');
        $this->addSql('ALTER TABLE etablissementadresse DROP FOREIGN KEY FK_F05539138B5749D3');
        $this->addSql('ALTER TABLE etablissementadresse ADD CONSTRAINT fk_etablissementadresse_adressecommune1 FOREIGN KEY (NUMINSEE_COMMUNE) REFERENCES adressecommune (NUMINSEE_COMMUNE) ON UPDATE CASCADE ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE etablissementadresse ADD CONSTRAINT fk_etablissementadresse_etablissement1 FOREIGN KEY (ID_ETABLISSEMENT) REFERENCES etablissement (ID_ETABLISSEMENT) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_f05539138b5749d3 ON etablissementadresse');
        $this->addSql('ALTER TABLE etablissementdossier DROP FOREIGN KEY FK_E42D132113C362E');
        $this->addSql('ALTER TABLE etablissementdossier DROP FOREIGN KEY FK_E42D1322763CF4F');
        $this->addSql('ALTER TABLE etablissementdossier ADD CONSTRAINT fk_etablissementdossier_dossier1 FOREIGN KEY (ID_DOSSIER) REFERENCES dossier (ID_DOSSIER) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE etablissementdossier ADD CONSTRAINT fk_etablissementdossier_etablissement1 FOREIGN KEY (ID_ETABLISSEMENT) REFERENCES etablissement (ID_ETABLISSEMENT) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_e42d132113c362e ON etablissementdossier');
        $this->addSql('DROP INDEX idx_e42d1322763cf4f ON etablissementdossier');
        $this->addSql('ALTER TABLE etablissementinformations DROP FOREIGN KEY FK_161D94DB2763CF4F');
        $this->addSql('ALTER TABLE etablissementinformations ADD CONSTRAINT fk_etablissementinformations_etablissement1 FOREIGN KEY (ID_ETABLISSEMENT) REFERENCES etablissement (ID_ETABLISSEMENT) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_161d94db2763cf4f ON etablissementinformations');
    }
}
