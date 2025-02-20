<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220151650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Renvoi d'avis Plat'AU";
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS `platauconsultation` (
            `ID_PLATAU` char(11),
            `STATUT_AVIS` varchar(50) DEFAULT NULL,
            `DATE_AVIS` date DEFAULT NULL,
            `STATUT_PEC` varchar(50) DEFAULT NULL,
            `DATE_PEC` date DEFAULT NULL,
            PRIMARY KEY (`ID_PLATAU`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE IF EXISTS `platauconsultation`');
    }
}
