<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
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
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf($schema->getTable('utilisateurpreferences')->hasColumn('DERNIERE_VERSION_VUE'), 'Dernière version vue déjà présente dans les préférences utilisateur.');

        $this->addSql('ALTER TABLE utilisateurpreferences ADD COLUMN DERNIERE_VERSION_VUE VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf(!$schema->getTable('utilisateurpreferences')->hasColumn('DERNIERE_VERSION_VUE'), 'Dernière version vue déjà supprimée des préférences utilisateur.');

        $this->addSql('ALTER TABLE utilisateurpreferences DROP COLUMN DERNIERE_VERSION_VUE');
    }
}
