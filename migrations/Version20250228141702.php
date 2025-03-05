<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250228141702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateurpreferences DROP INDEX fk_utilisateurpreferences_utilisateur1_idx, ADD UNIQUE INDEX UNIQ_B8C2949BF6170F81 (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE utilisateurpreferences DROP FOREIGN KEY fk_utilisateurpreferences_utilisateur1');
        $this->addSql('ALTER TABLE utilisateurpreferences CHANGE dashboard_blocs blocs_dashboard VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateurpreferences ADD CONSTRAINT FK_B8C2949BF6170F81 FOREIGN KEY (ID_UTILISATEUR) REFERENCES utilisateur (ID_UTILISATEUR)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateurpreferences DROP INDEX UNIQ_B8C2949BF6170F81, ADD INDEX fk_utilisateurpreferences_utilisateur1_idx (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE utilisateurpreferences DROP FOREIGN KEY FK_B8C2949BF6170F81');
        $this->addSql('ALTER TABLE utilisateurpreferences CHANGE blocs_dashboard DASHBOARD_BLOCS VARCHAR(500) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE utilisateurpreferences ADD CONSTRAINT fk_utilisateurpreferences_utilisateur1 FOREIGN KEY (ID_UTILISATEUR) REFERENCES utilisateur (ID_UTILISATEUR) ON UPDATE CASCADE ON DELETE CASCADE');
    }
}
