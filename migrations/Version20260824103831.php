<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824103831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate REF_DOCAJOUT and DATE_DOCAJOUT placeholder values to NULL in listedocajout table.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE `listedocajout` SET `REF_DOCAJOUT` = NULL WHERE `REF_DOCAJOUT` = \'\'');
        $this->addSql('UPDATE `listedocajout` SET `DATE_DOCAJOUT` = NULL WHERE `DATE_DOCAJOUT` = \'0000-00-00\'');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE `listedocajout` SET `REF_DOCAJOUT` = \'\' WHERE `REF_DOCAJOUT` IS NULL');
        $this->addSql('UPDATE `listedocajout` SET `DATE_DOCAJOUT` = \'0000-00-00\' WHERE `DATE_DOCAJOUT` IS NULL');
    }
}
