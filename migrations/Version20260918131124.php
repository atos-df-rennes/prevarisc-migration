<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260918131124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajout du numéro de version des pièces Plat'AU.";
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf($schema->getTable('piecejointe')->hasColumn('no_version'), 'Colonne no_version déjà présente sur les pièces jointes.');

        $this->addSql('ALTER TABLE piecejointe ADD no_version INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_E9A87772C7812B0F108FF17 ON piecejointe (ID_PLATAU, no_version)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf(!$schema->getTable('piecejointe')->hasColumn('no_version'), 'Colonne no_version déjà absente sur les pièces jointes.');

        $this->addSql('DROP INDEX IDX_E9A87772C7812B0F108FF17 ON piecejointe');
        $this->addSql('ALTER TABLE piecejointe DROP no_version');
    }
}
