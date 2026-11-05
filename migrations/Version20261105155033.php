<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261105155033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la référence courrier aux dossiers.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf($schema->getTable('dossier')->hasColumn('REFCOURRIER_DOSSIER'), 'La référence courrier est déjà présente sur les dossiers.');

        $this->addSql('ALTER TABLE dossier ADD COLUMN REFCOURRIER_DOSSIER VARCHAR(15) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf(!$schema->getTable('dossier')->hasColumn('REFCOURRIER_DOSSIER'), 'La référence courrier est déjà absente des dossiers.');

        $this->addSql('ALTER TABLE dossier DROP COLUMN REFCOURRIER_DOSSIER');
    }
}
