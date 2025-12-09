<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208155449 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Autorise la date de début du groupement de préventionnistes à être nulle.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groupementpreventionniste CHANGE DATEDEBUT_GROUPEMENTPREVENTIONNISTE DATEDEBUT_GROUPEMENTPREVENTIONNISTE DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groupementpreventionniste CHANGE DATEDEBUT_GROUPEMENTPREVENTIONNISTE DATEDEBUT_GROUPEMENTPREVENTIONNISTE DATETIME NOT NULL');
    }
}
