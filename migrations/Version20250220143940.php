<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220143940 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Ajout du type de champ "Parent".';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('champ')->hasColumn('ID_PARENT'), 'Type de champ "Parent" déjà existant.');

        $this->addSql("ALTER TABLE `champ` ADD column `ID_PARENT` bigint(20)");
        $this->addSql("ALTER TABLE `champ`
            ADD CONSTRAINT `fk_ID_PARENT_CHAMP`
            FOREIGN KEY (`ID_PARENT`)
            REFERENCES `champ`(`ID_CHAMP`)
            ON DELETE CASCADE ON UPDATE NO ACTION
        ");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('champ')->hasColumn('ID_PARENT'), 'Type de champ "Parent" non existant.');

        $this->addSql("ALTER TABLE `champ` DROP FOREIGN KEY `fk_ID_PARENT_CHAMP`");
        $this->addSql("ALTER TABLE `champ` DROP COLUMN `ID_PARENT`");
    }
}
