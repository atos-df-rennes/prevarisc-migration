<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220152723 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Levée de prescription.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('prescriptiondossier')->hasColumn('DATE_LEVEE'), 'Levée de prescription déjà existante.');
        
        $this->addSql("ALTER TABLE `prescriptiondossier` ADD COLUMN `DATE_LEVEE` DATE DEFAULT NULL");
        $this->addSql("ALTER TABLE `prescriptiondossier` ADD COLUMN `JUSTIFICATIF_LEVEE` TEXT DEFAULT NULL");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('prescriptiondossier')->hasColumn('DATE_LEVEE'), 'Levée de prescription non existante.');

        $this->addSql("ALTER TABLE `prescriptiondossier` DROP COLUMN `DATE_LEVEE`");
        $this->addSql("ALTER TABLE `prescriptiondossier` DROP COLUMN `JUSTIFICATIF_LEVEE`");
    }
}
