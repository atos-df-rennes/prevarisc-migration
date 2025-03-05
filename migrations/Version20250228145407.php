<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250228145407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX fk_utilisateur_groupe1_idx ON utilisateur');
        $this->addSql('ALTER IGNORE TABLE utilisateur CHANGE PASSWD_UTILISATEUR PASSWD_UTILISATEUR VARCHAR(255) DEFAULT NULL, CHANGE ID_GROUPE ID_GROUPE BIGINT UNSIGNED DEFAULT NULL');
        $this->addSql('DROP INDEX fk_utilisateurinformations_fonction1_idx ON utilisateurinformations');
        $this->addSql('DROP INDEX fk_utilisateurinformations_utilisateurcivilite1_idx ON utilisateurinformations');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_1D1C63B3524BECFF ON utilisateur');
        $this->addSql('ALTER IGNORE TABLE utilisateur CHANGE PASSWD_UTILISATEUR PASSWD_UTILISATEUR VARCHAR(32) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, CHANGE ID_GROUPE ID_GROUPE BIGINT UNSIGNED NOT NULL');
        $this->addSql('CREATE INDEX fk_utilisateur_groupe1_idx ON utilisateur (ID_GROUPE)');
        $this->addSql('CREATE INDEX fk_utilisateurinformations_fonction1_idx ON utilisateurinformations (ID_FONCTION)');
        $this->addSql('CREATE INDEX fk_utilisateurinformations_utilisateurcivilite1_idx ON utilisateurinformations (ID_UTILISATEURCIVILITE)');
    }
}
