<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251107152219 extends AbstractMigration
{
    public function getDescription() : string
    {
        return "Ajoute une nouvelle option d'avis pour Plat'AU.";
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE avis CHANGE LIBELLE_AVIS LIBELLE_AVIS VARCHAR(75) NOT NULL');
    }

    public function postUp(Schema $schema): void {
        $this->connection->executeStatement("INSERT IGNORE INTO `avis` VALUES (6,'Pas d\'avis - à motiver dans la partie Fondement de l\'avis')");
    }

    public function preDown(Schema $schema): void {
        $this->connection->executeStatement("DELETE FROM `avis` WHERE ID_AVIS = 6");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE avis CHANGE LIBELLE_AVIS LIBELLE_AVIS VARCHAR(30) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`');
    }
}
