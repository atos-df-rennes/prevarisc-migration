<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220141822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajout d'un bloc de tableau de bord pour les dossiers Plat'AU.";
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("INSERT IGNORE INTO `privileges`(`id_privilege`,`name`, `text`,`id_resource`) VALUES(211,'view_doss_platau_sans_etab', 'Dossiers Plat\'AU à traiter',100)");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM `privileges` WHERE `id_privilege` = 211');
    }
}
