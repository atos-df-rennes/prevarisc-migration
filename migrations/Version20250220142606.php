<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220142606 extends AbstractMigration
{
    public function getDescription() : string
    {
        return "Mise à jour réglementaire d'articles.";
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('etablissementinformations')->hasColumn('R14320_ETABLISSEMENTINFORMATIONS'), 'Articles déjà à jour.');

        $this->addSql("ALTER TABLE `etablissementinformations` CHANGE `R12320_ETABLISSEMENTINFORMATIONS` `R14320_ETABLISSEMENTINFORMATIONS` tinyint(1)");
        $this->addSql("UPDATE `prescriptionarticleliste` SET `LIBELLE_ARTICLE` = 'R. 143-20' WHERE `LIBELLE_ARTICLE` LIKE 'R. 123-20'");
        $this->addSql("UPDATE `prescriptionarticleliste` SET `LIBELLE_ARTICLE` = 'R. 143-16' WHERE `LIBELLE_ARTICLE` LIKE 'R. 123-16'");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('etablissementinformations')->hasColumn('R14320_ETABLISSEMENTINFORMATIONS'), 'Articles déjà à jour.');

        $this->addSql("ALTER TABLE `etablissementinformations` CHANGE `R14320_ETABLISSEMENTINFORMATIONS` `R12320_ETABLISSEMENTINFORMATIONS` tinyint(1)");
        $this->addSql("UPDATE `prescriptionarticleliste` SET `LIBELLE_ARTICLE` = 'R. 123-20' WHERE `LIBELLE_ARTICLE` LIKE 'R. 143-20'");
        $this->addSql("UPDATE `prescriptionarticleliste` SET `LIBELLE_ARTICLE` = 'R. 123-16' WHERE `LIBELLE_ARTICLE` LIKE 'R. 143-16'");
    }
}
