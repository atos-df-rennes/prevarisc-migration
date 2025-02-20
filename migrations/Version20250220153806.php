<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220153806 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Bloc de tableau de bord des pièces jointes en erreur.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("INSERT IGNORE INTO `privileges` (`id_privilege`,`name`, `text`, `id_resource`) VALUES (217,'view_doss_platau_pjs_en_erreur', 'Dossiers Plat''AU pièces jointes non envoyées', 100)");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` = 217");
    }
}
