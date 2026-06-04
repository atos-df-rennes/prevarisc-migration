<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250911081338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Réordonnancement des valeurs de liste.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf($schema->getTable('champvaleurliste')->hasColumn('idx'), 'Index déjà présent sur les valeurs de liste.');

        $this->addSql('ALTER TABLE champvaleurliste ADD idx INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf(!$schema->getTable('champvaleurliste')->hasColumn('idx'), 'Index déjà supprimé des valeurs de liste.');

        $this->addSql('ALTER TABLE champvaleurliste DROP idx');
    }
}
