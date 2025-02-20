<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220150150 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Libellé des types texte.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE `listetypechamprubrique` SET `TYPE` = 'Texte' WHERE `ID_TYPECHAMP` = 1");
        $this->addSql("UPDATE `listetypechamprubrique` SET `TYPE` = 'Texte long' WHERE `ID_TYPECHAMP` = 2");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
