<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250227151024 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE groupe CHANGE DESC_GROUPE DESC_GROUPE LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE groupe CHANGE DESC_GROUPE DESC_GROUPE TEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`');
    }
}
