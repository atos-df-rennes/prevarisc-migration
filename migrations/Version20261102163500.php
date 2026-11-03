<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261102163500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne la contrainte NOT NULL du libellé des changements.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf($schema->getTable('changement')->getColumn('LIBELLE_CHANGEMENT')->getNotnull(), 'Le libellé des changements est déjà obligatoire.');
        $this->abortIf(
            false !== $this->connection->fetchOne('SELECT 1 FROM changement WHERE LIBELLE_CHANGEMENT IS NULL LIMIT 1'),
            'Des changements ont un libellé NULL. Corrigez ces données avant de relancer la migration.'
        );

        $this->addSql('ALTER TABLE changement CHANGE LIBELLE_CHANGEMENT LIBELLE_CHANGEMENT VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf(!$schema->hasTable('changement'), 'Table sur les changements non existante.');
        $this->skipIf(!$schema->getTable('changement')->getColumn('LIBELLE_CHANGEMENT')->getNotnull(), 'Le libellé des changements est déjà nullable.');

        $this->addSql('ALTER TABLE changement CHANGE LIBELLE_CHANGEMENT LIBELLE_CHANGEMENT VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`');
    }
}
