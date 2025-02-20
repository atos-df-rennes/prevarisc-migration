<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220140716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Correction du type de dossier de nature "levée de réserve suite à un avis défavorable".';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE `dossiernatureliste` SET `ID_DOSSIERTYPE` = 1 WHERE `dossiernatureliste`.`ID_DOSSIERNATURE` = 19');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
