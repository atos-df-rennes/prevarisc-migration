<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260915124725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Sauvegarde de l'état d'ouverture d'une rubrique à l'enregistrement du descriptif.";
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf($schema->getTable('displayrubriquedossier')->hasColumn('USER_OPENED') && $schema->getTable('displayrubriqueetablissement')->hasColumn('USER_OPENED'), "Colonne USER_OPENED déjà présente sur les préférences d'affichage.");

        $this->addSql('ALTER TABLE displayrubriquedossier ADD USER_OPENED TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE displayrubriqueetablissement ADD USER_OPENED TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf(!$schema->getTable('displayrubriquedossier')->hasColumn('USER_OPENED') && !$schema->getTable('displayrubriqueetablissement')->hasColumn('USER_OPENED'), "Colonne USER_OPENED déjà absente des préférences d'affichage.");

        $this->addSql('ALTER TABLE displayrubriquedossier DROP USER_OPENED');
        $this->addSql('ALTER TABLE displayrubriqueetablissement DROP USER_OPENED');
    }
}
