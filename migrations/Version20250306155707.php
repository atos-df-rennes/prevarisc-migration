<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250306155707 extends AbstractMigration
{
    public function getDescription() : string
    {
        return "Correction d'une mauvaise valeur pour la commune de l'utilisateur.";
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE utilisateur SET NUMINSEE_COMMUNE = NULL where NUMINSEE_COMMUNE = 'null'");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE utilisateur SET NUMINSEE_COMMUNE = 'null' where NUMINSEE_COMMUNE = NULL");
    }
}
