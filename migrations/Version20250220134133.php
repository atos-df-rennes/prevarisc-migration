<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220134133 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Export des recherches.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("INSERT IGNORE INTO `resources`(`id_resource`,`name`, `text`) VALUES(200,'export', 'Export Calc')");
        $this->addSql("INSERT IGNORE INTO `privileges`(`id_privilege`,`name`, `text`,`id_resource`) VALUES(200,'export_ets', 'Etablissements',200)");
        $this->addSql("INSERT IGNORE INTO `privileges`(`id_privilege`,`name`, `text`,`id_resource`) VALUES(210,'export_doss', 'Dossiers',200)");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` = 200");
        $this->addSql("DELETE FROM `resources` WHERE `id_resource` = 200");
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` = 210");
    }
}
