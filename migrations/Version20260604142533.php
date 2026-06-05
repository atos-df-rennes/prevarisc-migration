<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260604142533 extends AbstractMigration
{
    public function getDescription() : string
    {
        return "Sauvegarde l'état d'ouverture d'une rubrique à la sauvegarde.";
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE displayrubriqueetablissement ADD USER_OPENED TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE displayrubriquedossier ADD USER_OPENED TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE displayrubriqueetablissement DROP USER_OPENED');
        $this->addSql('ALTER TABLE displayrubriquedossier DROP USER_OPENED');
    }
}
