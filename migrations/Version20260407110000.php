<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ DERNIERE_VERSION_VUE dans utilisateurpreferences pour le bandeau changelog.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE utilisateurpreferences ADD COLUMN DERNIERE_VERSION_VUE VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE utilisateurpreferences DROP COLUMN DERNIERE_VERSION_VUE');
    }
}
