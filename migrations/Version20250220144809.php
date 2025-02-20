<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220144809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modification de la structure des avis et dérogations.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('avisderogations')->hasColumn('INFORMATIONS'), 'Structure des avis et dérogations déjà modifiée.');

        $this->addSql('ALTER TABLE `avisderogations` DROP COLUMN `INFORMATIONS`');

        $this->addSql('ALTER TABLE `avisderogations` ADD COLUMN `OBJET` TEXT DEFAULT NULL AFTER `TITRE`');
        $this->addSql('ALTER TABLE `avisderogations` ADD COLUMN `JUSTIFICATION` TEXT DEFAULT NULL AFTER `OBJET`');
        $this->addSql('ALTER TABLE `avisderogations` ADD COLUMN `ARTICLES_CONCERNES` TEXT DEFAULT NULL AFTER `JUSTIFICATION`');
        $this->addSql('ALTER TABLE `avisderogations` ADD COLUMN `MESURES_COMPENSATOIRES_PROPOSEES` TEXT DEFAULT NULL AFTER `ARTICLES_CONCERNES`');
        $this->addSql('ALTER TABLE `avisderogations` ADD COLUMN `ANALYSE_DE_RISQUE` TEXT DEFAULT NULL AFTER `MESURES_COMPENSATOIRES_PROPOSEES`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('avisderogations')->hasColumn('INFORMATIONS'), 'Structure des avis et dérogations déjà modifiée.');

        $this->addSql('ALTER TABLE `avisderogations` DROP COLUMN `OBJET`');
        $this->addSql('ALTER TABLE `avisderogations` DROP COLUMN `JUSTIFICATION`');
        $this->addSql('ALTER TABLE `avisderogations` DROP COLUMN `ARTICLES_CONCERNES`');
        $this->addSql('ALTER TABLE `avisderogations` DROP COLUMN `MESURES_COMPENSATOIRES_PROPOSEES`');
        $this->addSql('ALTER TABLE `avisderogations` DROP COLUMN `ANALYSE_DE_RISQUE`');

        $this->addSql('ALTER TABLE `avisderogations` ADD COLUMN `INFORMATIONS` TEXT DEFAULT NULL');
    }
}
