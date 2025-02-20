<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220152612 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Date de réponse attendue.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('platauconsultation')->hasColumn('DATE_REPONSE_ATTENDUE'), 'Date de réponse attendue déjà existante.');

        $this->addSql("ALTER TABLE `platauconsultation` ADD column `DATE_REPONSE_ATTENDUE` date DEFAULT NULL");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('platauconsultation')->hasColumn('DATE_REPONSE_ATTENDUE'), 'Date de réponse attendue non existante.');

        $this->addSql("ALTER TABLE `platauconsultation` DROP COLUMN `DATE_REPONSE_ATTENDUE`");
    }
}
