<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260916090201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corrige les données des dossiers étant liés à des commissions invalides.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE dossier
            SET COMMISSION_DOSSIER = NULL
            WHERE COMMISSION_DOSSIER IS NOT NULL
            AND COMMISSION_DOSSIER NOT IN (SELECT ID_COMMISSION FROM commission)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->throwIrreversibleMigrationException('Impossible de remettre des valeurs invalides.');
    }
}
