<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220142344 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Ordonnancement des couches cartographiques.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('couchecarto')->hasColumn('ORDRE_COUCHECARTO'), 'Ordonnancement des couches cartographiques déjà existant.');

        $this->addSql("ALTER TABLE `couchecarto` ADD COLUMN `ORDRE_COUCHECARTO` int(2) DEFAULT NULL");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('couchecarto')->hasColumn('ORDRE_COUCHECARTO'), 'Ordonnancement des couches cartographiques non existant.');

        $this->addSql("ALTER TABLE `couchecarto` DROP COLUMN `ORDRE_COUCHECARTO`");
    }
}
