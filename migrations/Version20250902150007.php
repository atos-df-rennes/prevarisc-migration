<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250902150007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Remet à jour le nom d'une colonne modifié en erreur.";
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('utilisateurpreferences')->hasColumn('DASHBOARD_BLOCS'), 'Nom de colonne déjà correct.');

        $this->addSql('ALTER TABLE utilisateurpreferences CHANGE blocs_dashboard DASHBOARD_BLOCS VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
