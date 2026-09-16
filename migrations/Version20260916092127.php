<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260916092127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute une contrainte de clé étrangère entre le dossier et sa commission.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE dossier CHANGE COMMISSION_DOSSIER COMMISSION_DOSSIER BIGINT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E0376ADCD1A5 FOREIGN KEY (COMMISSION_DOSSIER) REFERENCES commission (ID_COMMISSION) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3D48E0376ADCD1A5 ON dossier (COMMISSION_DOSSIER)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E0376ADCD1A5');
        $this->addSql('DROP INDEX IDX_3D48E0376ADCD1A5 ON dossier');
        $this->addSql('ALTER TABLE dossier CHANGE COMMISSION_DOSSIER COMMISSION_DOSSIER BIGINT DEFAULT NULL');
    }
}
