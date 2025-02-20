<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220145107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Valeurs multiples pour un champ.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('champ')->hasColumn('tableau'), 'Valeurs multiples pour un champ déjà existantes.');

        $this->addSql('ALTER TABLE `champ` ADD column `tableau` BOOLEAN');
        $this->addSql('ALTER TABLE `valeur` ADD column `idx` INTEGER');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('champ')->hasColumn('tableau'), 'Valeurs multiples pour un champ non existantes.');

        $this->addSql('ALTER TABLE `champ` DROP column `tableau`');
        $this->addSql('ALTER TABLE `valeur` DROP column `idx`');
    }
}
