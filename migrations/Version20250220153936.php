<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220153936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Meilleure description des pièces jointes Plat'AU.";
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('piecejointe')->hasColumn('TYPE'), "Meilleure description des pièces jointes Plat'AU déjà existante.");

        $this->addSql('ALTER TABLE `piecejointe` ADD COLUMN `TYPE` VARCHAR(255) NULL DEFAULT NULL');
        $this->addSql('ALTER TABLE `piecejointe` ADD COLUMN `SOUS_TYPE` VARCHAR(255) NULL DEFAULT NULL');
        $this->addSql('ALTER TABLE `piecejointe` ADD COLUMN `NATURE` VARCHAR(255) NULL DEFAULT NULL');
        $this->addSql('ALTER TABLE `piecejointe` ADD COLUMN `DATE_DEPOT` DATE NULL DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('piecejointe')->hasColumn('TYPE'), "Meilleure description des pièces jointes Plat'AU non existante.");

        $this->addSql('ALTER TABLE `piecejointe` DROP COLUMN `TYPE`');
        $this->addSql('ALTER TABLE `piecejointe` DROP COLUMN `SOUS_TYPE`');
        $this->addSql('ALTER TABLE `piecejointe` DROP COLUMN `NATURE`');
        $this->addSql('ALTER TABLE `piecejointe` DROP COLUMN `DATE_DEPOT`');
    }
}
