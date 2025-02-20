<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220154139 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Modification du genre EIC par BUP.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE `genre` SET `LIBELLE_GENRE` = 'BUP' WHERE `LIBELLE_GENRE` = 'EIC'");
        $this->addSql("UPDATE `resources` 
            SET `name` = REPLACE(name, 'eic', 'bup'), `text` = REPLACE(text, 'EIC', 'BUP')
            WHERE `name` LIKE 'etablissement_eic%'
        ");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE `genre` SET `LIBELLE_GENRE` = 'EIC' WHERE `LIBELLE_GENRE` = 'BUP'");
        $this->addSql("UPDATE `resources` 
            SET `name` = REPLACE(name, 'bup', 'eic'), `text` = REPLACE(text, 'BUP', 'EIC')
            WHERE `name` LIKE 'etablissement_bup%'
        ");
    }
}
