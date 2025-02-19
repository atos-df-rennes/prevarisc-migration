<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250219143031 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Renforcement de la sécurité.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('utilisateur')->hasColumn('FAILED_LOGIN_ATTEMPTS_UTILISATEUR'), 'Renforcement de la sécurité déjà existant.');

        $this->addSql("ALTER TABLE `utilisateur` ADD `FAILED_LOGIN_ATTEMPTS_UTILISATEUR` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `ACTIF_UTILISATEUR`");
        $this->addSql("ALTER TABLE `utilisateur` ADD `IP_UTILISATEUR` VARCHAR(45) DEFAULT NULL AFTER `FAILED_LOGIN_ATTEMPTS_UTILISATEUR`");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('utilisateur')->hasColumn('FAILED_LOGIN_ATTEMPTS_UTILISATEUR'), 'Renforcement de la sécurité non existant.');

        $this->addSql("ALTER TABLE `utilisateur` DROP COLUMN `IP_UTILISATEUR`");
        $this->addSql("ALTER TABLE `utilisateur` DROP COLUMN `FAILED_LOGIN_ATTEMPTS_UTILISATEUR`");
    }
}
