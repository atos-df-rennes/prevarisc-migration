<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824102109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make REF_CONSULTE and DATE_CONSULTE nullable in dossierdocconsulte table and migrate placeholder values to NULL.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        // Make columns nullable
        $this->addSql('ALTER TABLE `dossierdocconsulte` CHANGE `REF_CONSULTE` `REF_CONSULTE` VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `dossierdocconsulte` CHANGE `DATE_CONSULTE` `DATE_CONSULTE` DATE DEFAULT NULL');
    }

    public function postUp(Schema $schema): void
    {
        // Migrate placeholder values to NULL now that the columns accept it
        $this->connection->executeStatement('UPDATE `dossierdocconsulte` SET `REF_CONSULTE` = NULL WHERE `REF_CONSULTE` = \'\'');
        $this->connection->executeStatement('UPDATE `dossierdocconsulte` SET `DATE_CONSULTE` = NULL WHERE `DATE_CONSULTE` = \'0000-00-00\'');
    }

    public function preDown(Schema $schema): void
    {
        // Restore placeholder values before the columns are made NOT NULL again
        $this->connection->executeStatement('UPDATE `dossierdocconsulte` SET `REF_CONSULTE` = \'\' WHERE `REF_CONSULTE` IS NULL');
        $this->connection->executeStatement('UPDATE `dossierdocconsulte` SET `DATE_CONSULTE` = \'0000-00-00\' WHERE `DATE_CONSULTE` IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        // Make columns NOT NULL again
        $this->addSql('ALTER TABLE `dossierdocconsulte` CHANGE `REF_CONSULTE` `REF_CONSULTE` VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE `dossierdocconsulte` CHANGE `DATE_CONSULTE` `DATE_CONSULTE` DATE NOT NULL');
    }
}
