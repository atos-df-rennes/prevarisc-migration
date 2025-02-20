<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220144338 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Ordonnancement des champs et rubriques.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('champ')->hasColumn('idx'), 'Ordonnancement des champs et rubriques déjà existant.');

        $this->addSql("ALTER TABLE `champ` ADD column idx INTEGER");
        $this->addSql("ALTER TABLE `rubrique` ADD column idx INTEGER");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('champ')->hasColumn('idx'), 'Ordonnancement des champs et rubriques non existant.');

        $this->addSql("ALTER TABLE `champ` DROP COLUMN idx");
        $this->addSql("ALTER TABLE `rubrique` DROP COLUMN idx");
    }
}
