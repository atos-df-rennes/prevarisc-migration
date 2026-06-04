<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le flag manifestation temporaire sur les membres de commission.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf($schema->getTable('commissionmembre')->hasColumn('MANIFESTATION_COMMISSIONMEMBRE'), "Flag manifestation déjà présent dans les règles de commission.");

        $this->addSql('ALTER TABLE `commissionmembre` ADD `MANIFESTATION_COMMISSIONMEMBRE` TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf(!$schema->getTable('commissionmembre')->hasColumn('MANIFESTATION_COMMISSIONMEMBRE'), "Flag manifestation déjà supprimé des règles de commission.");

        $this->addSql('ALTER TABLE `commissionmembre` DROP `MANIFESTATION_COMMISSIONMEMBRE`');
    }
}
