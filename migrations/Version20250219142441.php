<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250219142441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alerte mail sur les changements.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("INSERT IGNORE INTO `resources`(`id_resource`,`name`, `text`) VALUES (56,'alerte_email','Alertes')");
        $this->addSql("INSERT IGNORE INTO `privileges`(`id_privilege`,`name`, `text`,`id_resource`) VALUES 
            (60,'alerte_statut','Changement de statut',56),
            (61,'alerte_avis','Changement d''avis',56),
            (62,'alerte_classement','Changement de classement',56)
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM `privileges` WHERE `id_privilege` IN (60, 61, 62)');
        $this->addSql('DELETE FROM `resources` WHERE `id_resource` = 56');
    }
}
