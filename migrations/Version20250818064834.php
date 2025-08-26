<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250818064834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Harmonise le comportement des avis.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE dossier SET AVIS_DOSSIER_COMMISSION = NULL WHERE AVIS_DOSSIER_COMMISSION = 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE dossier SET AVIS_DOSSIER_COMMISSION = 0 WHERE AVIS_DOSSIER_COMMISSION IS NULL');
    }
}
