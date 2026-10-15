<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250220153249 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize empty utilisateur.roles immediately after the column is created.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        // Run before later migrations without renaming the already published Version20260727161630.
        $this->addSql('UPDATE `utilisateur` SET `roles` = \'[]\' WHERE `roles` = \'\' OR `roles` IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        // Keep valid JSON rather than restoring invalid historical values.
    }
}
