<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220140918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Typo dans le nom d'une activité.";
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE `typeactivite` SET `LIBELLE_ACTIVITE` = 'Salles polyvalentes non visées par le Type X (salle polyvalente qui n''a pas une destination unique)' WHERE ID_TYPEACTIVITE = 25");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
