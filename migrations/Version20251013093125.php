<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251013093125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Augmentation de la taille des champs TYPE et SOUS-TYPE.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf('text' === $schema->getTable('piecejointe')->getColumn('TYPE')->getType()->getName(), 'Colonnes TYPE et SOUS_TYPE de piecejointe déjà en LONGTEXT.');

        $this->addSql('ALTER TABLE piecejointe CHANGE TYPE TYPE LONGTEXT DEFAULT NULL, CHANGE SOUS_TYPE SOUS_TYPE LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf('string' === $schema->getTable('piecejointe')->getColumn('TYPE')->getType()->getName(), 'Colonnes TYPE et SOUS_TYPE de piecejointe déjà en VARCHAR(255).');

        $this->addSql('ALTER TABLE piecejointe CHANGE TYPE TYPE VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, CHANGE SOUS_TYPE SOUS_TYPE VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`');
    }
}
