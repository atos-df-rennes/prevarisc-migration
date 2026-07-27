<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260727161630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert empty strings to valid JSON arrays in utilisateur.roles column.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        // Convert empty strings to valid JSON array
        $this->addSql('UPDATE `utilisateur` SET `roles` = \'[]\' WHERE `roles` = \'\' OR `roles` IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        // No rollback needed - converting empty to [] is safe
    }
}
