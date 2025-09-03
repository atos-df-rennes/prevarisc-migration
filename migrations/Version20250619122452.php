<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250619122452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE couchecarto CHANGE ID_COUCHECARTO ID_COUCHECARTO INT AUTO_INCREMENT NOT NULL, CHANGE ISBASELAYER_COUCHECARTO ISBASELAYER_COUCHECARTO TINYINT(1) NOT NULL, CHANGE FORMAT_COUCHECARTO FORMAT_COUCHECARTO VARCHAR(255) NOT NULL, CHANGE TRANSPARENT_COUCHECARTO TRANSPARENT_COUCHECARTO TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE couchecarto CHANGE ID_COUCHECARTO ID_COUCHECARTO BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE ISBASELAYER_COUCHECARTO ISBASELAYER_COUCHECARTO TINYINT(1) DEFAULT NULL, CHANGE FORMAT_COUCHECARTO FORMAT_COUCHECARTO VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, CHANGE TRANSPARENT_COUCHECARTO TRANSPARENT_COUCHECARTO TINYINT(1) DEFAULT NULL');
    }
}
