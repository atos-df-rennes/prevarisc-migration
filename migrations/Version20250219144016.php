<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250219144016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Droits sur la recherche.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("INSERT IGNORE INTO `resources`(`id_resource`,`name`, `text`) VALUES (57,'search','Recherche')");
        $this->addSql("INSERT IGNORE INTO `privileges`(`id_privilege`,`name`, `text`,`id_resource`) VALUES 
            (63,'search_ets','Etablissements',57),
            (64,'search_dossiers','Dossiers',57)
        ");
        $this->addSql('INSERT IGNORE INTO `groupe-privileges`(`ID_GROUPE`, `id_privilege`) select id_groupe,63 from groupe');
        $this->addSql('INSERT IGNORE INTO `groupe-privileges`(`ID_GROUPE`, `id_privilege`) select id_groupe,64 from groupe');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM `groupe-privileges` WHERE `id_privilege` IN (63, 64)');
        $this->addSql('DELETE FROM `privileges` WHERE `id_privilege` IN (63, 64)');
        $this->addSql('DELETE FROM `resources` WHERE `id_resource` = 57');
    }
}
