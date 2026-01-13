<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260113071712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Correction d'orthographe sur les libellés de type R.";
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE `typeactivite` SET `LIBELLE_ACTIVITE` = REPLACE(LIBELLE_ACTIVITE, 'Lycee', 'Lycée') WHERE `LIBELLE_ACTIVITE` LIKE '%Lycee%'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
