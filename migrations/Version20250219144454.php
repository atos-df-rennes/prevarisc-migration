<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250219144454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Droit de vision sur toutes les commissions.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("INSERT IGNORE INTO `privileges` (`id_privilege`, `name`, `text`, `id_resource`) VALUES (80, 'calendar_view_all', 'Voir tous les dossiers et commissions', 2)");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM `privileges` WHERE `id_privilege` = 80');
    }
}
