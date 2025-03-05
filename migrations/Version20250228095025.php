<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250228095025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur CHANGE DERNIERE_VISITE_DASHBOARD DERNIERE_VISITE_DASHBOARD DATETIME DEFAULT NULL, CHANGE DERNIERE_VISITE_PIECES_DOSSIER DERNIERE_VISITE_PIECES_DOSSIER DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur CHANGE DERNIERE_VISITE_DASHBOARD DERNIERE_VISITE_DASHBOARD DATETIME NOT NULL, CHANGE DERNIERE_VISITE_PIECES_DOSSIER DERNIERE_VISITE_PIECES_DOSSIER DATETIME NOT NULL');
    }
}
