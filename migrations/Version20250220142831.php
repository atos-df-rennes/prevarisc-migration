<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220142831 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Traçabilité des suppressions.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('etablissement')->hasColumn('DELETED_BY'), 'Traçabilité des suppressions déjà existante.');

        $this->addSql("ALTER TABLE etablissement ADD COLUMN `DELETED_BY` bigint(20) unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE dossier ADD COLUMN `DELETED_BY` bigint(20) unsigned DEFAULT NULL");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('etablissement')->hasColumn('DELETED_BY'), 'Traçabilité des suppressions non existante.');

        $this->addSql("ALTER TABLE etablissement DROP COLUMN `DELETED_BY`");
        $this->addSql("ALTER TABLE dossier DROP COLUMN `DELETED_BY`");
    }
}
