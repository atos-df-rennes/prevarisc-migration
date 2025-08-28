<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250828101202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'La colonne ID_UTILISATEURINFORMATIONS peut ne pas être renseignée.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE adressecommune CHANGE ID_UTILISATEURINFORMATIONS ID_UTILISATEURINFORMATIONS BIGINT UNSIGNED DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE adressecommune CHANGE ID_UTILISATEURINFORMATIONS ID_UTILISATEURINFORMATIONS BIGINT UNSIGNED NOT NULL');
    }
}
