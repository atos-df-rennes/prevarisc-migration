<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260414064443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Autorise les numéros d'intervention non-entiers.";
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf('string' === $schema->getTable('dossier')->getColumn('NUMINTERV_DOSSIER')->getType()->getName(), 'Colonne NUMINTERV_DOSSIER déjà en VARCHAR(255).');

        $this->addSql('ALTER TABLE `dossier` CHANGE `NUMINTERV_DOSSIER` `NUMINTERV_DOSSIER` VARCHAR(255) NULL DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf('integer' === $schema->getTable('dossier')->getColumn('NUMINTERV_DOSSIER')->getType()->getName(), 'Colonne NUMINTERV_DOSSIER déjà en INT.');

        $this->addSql('ALTER TABLE `dossier` CHANGE `NUMINTERV_DOSSIER` `NUMINTERV_DOSSIER` INT NULL DEFAULT NULL');
    }
}
