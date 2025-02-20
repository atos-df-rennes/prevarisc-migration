<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220141023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajout de l'identifiant Plat'AU.";
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('dossier')->hasColumn('ID_PLATAU'), "Identifiant Plat'AU déjà existant.");

        $this->addSql('ALTER TABLE `dossier` ADD `ID_PLATAU` CHAR(11) NULL DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('dossier')->hasColumn('ID_PLATAU'), "Identifiant Plat'AU non existant.");

        $this->addSql('ALTER TABLE `dossier` DROP COLUMN `ID_PLATAU`');
    }
}
