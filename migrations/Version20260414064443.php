<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260414064443 extends AbstractMigration
{
    public function getDescription() : string
    {
        return "Autorise les numéros d'intervention non-entiers.";
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `dossier` CHANGE `NUMINTERV_DOSSIER` `NUMINTERV_DOSSIER` VARCHAR(255) NULL DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `dossier` CHANGE `NUMINTERV_DOSSIER` `NUMINTERV_DOSSIER` INT NULL DEFAULT NULL');
    }
}
