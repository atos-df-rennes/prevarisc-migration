<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909092425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate DOCUMENT_CR placeholder empty string values to NULL in commission table.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE `commission` SET `DOCUMENT_CR` = NULL WHERE `DOCUMENT_CR` = \'\'');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        // Non réversible : on ne peut pas distinguer les commissions qui avaient '' de celles qui avaient déjà NULL.
        $this->throwIrreversibleMigrationException();
    }
}
