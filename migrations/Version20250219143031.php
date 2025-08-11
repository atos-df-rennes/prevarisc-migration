<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250219143031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Notification de nouveaux éléments.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('dossier')->hasColumn('DATE_NOTIFICATION'), 'Notification de nouveaux éléments déjà existante.');

        $this->addSql('ALTER TABLE `dossier` ADD COLUMN `DATE_NOTIFICATION` DATETIME NULL DEFAULT NULL');
        $this->addSql('ALTER TABLE `piecejointe` ADD COLUMN `DATE_NOTIFICATION` DATETIME NULL DEFAULT NULL');
        $this->addSql('ALTER TABLE `utilisateur` ADD COLUMN `DERNIERE_VISITE_DASHBOARD` DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE `utilisateur` ADD COLUMN `DERNIERE_VISITE_PIECES_DOSSIER` DATETIME DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('dossier')->hasColumn('DATE_NOTIFICATION'), 'Notification de nouveaux éléments non existante.');

        $this->addSql('ALTER TABLE `dossier` DROP COLUMN `DATE_NOTIFICATION`');
        $this->addSql('ALTER TABLE `piecejointe` DROP COLUMN `DATE_NOTIFICATION`');
        $this->addSql('ALTER TABLE `utilisateur` DROP COLUMN `DERNIERE_VISITE_DASHBOARD`');
        $this->addSql('ALTER TABLE `utilisateur` DROP COLUMN `DERNIERE_VISITE_PIECES_DOSSIER`');
    }
}
