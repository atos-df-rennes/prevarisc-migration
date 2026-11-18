<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118180352 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reprend le mode SSO des comptes historiques et le rend non nullable.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE utilisateur SET sso = 1 WHERE (sso IS NULL OR sso = 0) AND USERNAME_UTILISATEUR <> '' AND PASSWD_UTILISATEUR IS NULL");
        $this->addSql('UPDATE utilisateur SET sso = 0 WHERE sso IS NULL');
        $this->addSql('ALTER TABLE utilisateur MODIFY sso TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        // Les anciens NULL et les choix explicites ne peuvent plus être distingués.
        $this->addSql('ALTER TABLE utilisateur MODIFY sso TINYINT(1) DEFAULT NULL');
    }
}
