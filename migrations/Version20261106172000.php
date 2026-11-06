<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261106172000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Augmente la capacité du nom des ressources spécialisées.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE resources MODIFY name TEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');
        $this->abortIf(
            false !== $this->connection->fetchOne('SELECT 1 FROM resources WHERE CHAR_LENGTH(name) > 255 LIMIT 1'),
            'Des noms de ressources dépassent 255 caractères ; impossible de réduire la colonne sans perte de données.'
        );

        $this->addSql('ALTER TABLE resources MODIFY name VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`');
    }
}
