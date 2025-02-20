<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220143014 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Meilleure précision des coordonnées GPS.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `etablissementadresse` MODIFY `LON_ETABLISSEMENTADRESSE` float(9,6)");
        $this->addSql("ALTER TABLE `etablissementadresse` MODIFY `LAT_ETABLISSEMENTADRESSE` float(8,6)");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `etablissementadresse` MODIFY `LON_ETABLISSEMENTADRESSE` float");
        $this->addSql("ALTER TABLE `etablissementadresse` MODIFY `LAT_ETABLISSEMENTADRESSE` float");
    }
}
