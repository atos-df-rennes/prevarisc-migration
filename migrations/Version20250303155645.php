<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250303155645 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE adressecommune CHANGE ID_UTILISATEURINFORMATIONS ID_UTILISATEURINFORMATIONS BIGINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE adressecommune ADD CONSTRAINT FK_D404E385B2A0DA51 FOREIGN KEY (ID_UTILISATEURINFORMATIONS) REFERENCES utilisateurinformations (ID_UTILISATEURINFORMATIONS)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D404E385B2A0DA51 ON adressecommune (ID_UTILISATEURINFORMATIONS)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE adressecommune DROP FOREIGN KEY FK_D404E385B2A0DA51');
        $this->addSql('DROP INDEX UNIQ_D404E385B2A0DA51 ON adressecommune');
        $this->addSql('ALTER TABLE adressecommune CHANGE ID_UTILISATEURINFORMATIONS ID_UTILISATEURINFORMATIONS BIGINT NOT NULL');
    }
}
