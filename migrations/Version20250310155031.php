<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250310155031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Libellé des avis provenant de Plat'AU.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier ADD AVIS_DOSSIER_COMMISSION_LIBELLE VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier DROP AVIS_DOSSIER_COMMISSION_LIBELLE');
    }
}
