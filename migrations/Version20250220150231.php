<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220150231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du type de champ "Date".';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('valeur')->hasColumn('VALEUR_DATE'), 'Type de champ "Date" déjà existant.');

        $this->addSql('ALTER TABLE `valeur` ADD column `VALEUR_DATE` DATE DEFAULT NULL AFTER `VALEUR_CHECKBOX`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('valeur')->hasColumn('VALEUR_DATE'), 'Type de champ "Date" non existant.');

        $this->addSql('ALTER TABLE `valeur` DROP column `VALEUR_DATE`');
    }
}
