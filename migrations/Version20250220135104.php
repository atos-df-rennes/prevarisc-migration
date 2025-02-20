<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220135104 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Gestion des emplacements camping.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('etablissementinformations')->hasColumn('EFFECTIFCARAVANE__ETABLISSEMENTINFORMATIONS'), 'Gestion des emplacements camping déjà existante.');

        $this->addSql("ALTER TABLE`etablissementinformations` ADD COLUMN `EFFECTIFCARAVANE__ETABLISSEMENTINFORMATIONS` int(11) NULL DEFAULT NULL");
        $this->addSql("ALTER TABLE`etablissementinformations` ADD COLUMN `EFFECTIFHABITATION_ETABLISSEMENTINFORMATIONS` int(11) NULL DEFAULT NULL");
        $this->addSql("ALTER TABLE`etablissementinformations` ADD COLUMN `EFFECTIFEMPLACEMENTNU_ETABLISSEMENTINFORMATIONS` int(11) NULL DEFAULT NULL");
        $this->addSql("ALTER TABLE`etablissementinformations` ADD COLUMN `EFFECTIFDIVERS_ETABLISSEMENTINFORMATIONS` int(11) NULL DEFAULT NULL");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('etablissementinformations')->hasColumn('EFFECTIFCARAVANE__ETABLISSEMENTINFORMATIONS'), 'Gestion des emplacements camping non existante.');

        $this->addSql("ALTER TABLE`etablissementinformations` DROP COLUMN `EFFECTIFCARAVANE__ETABLISSEMENTINFORMATIONS`");
        $this->addSql("ALTER TABLE`etablissementinformations` DROP COLUMN `EFFECTIFHABITATION_ETABLISSEMENTINFORMATIONS`");
        $this->addSql("ALTER TABLE`etablissementinformations` DROP COLUMN `EFFECTIFEMPLACEMENTNU_ETABLISSEMENTINFORMATIONS`");
        $this->addSql("ALTER TABLE`etablissementinformations` DROP COLUMN `EFFECTIFDIVERS_ETABLISSEMENTINFORMATIONS`");
    }
}
