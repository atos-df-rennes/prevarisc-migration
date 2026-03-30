<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330131444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rend la colonne idx non-nullable sur rubrique, champ et champvaleurliste (valeurs NULL remplacées par -1)';
    }

    public function preUp(Schema $schema): void {
        $this->addSql('UPDATE rubrique SET idx = -1 WHERE idx IS NULL');
        $this->addSql('UPDATE champ SET idx = -1 WHERE idx IS NULL');
        $this->addSql('UPDATE champvaleurliste SET idx = -1 WHERE idx IS NULL');
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rubrique CHANGE idx idx INT NOT NULL');
        $this->addSql('ALTER TABLE champ CHANGE idx idx INT NOT NULL');
        $this->addSql('ALTER TABLE champvaleurliste CHANGE idx idx INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rubrique CHANGE idx idx INT DEFAULT NULL');
        $this->addSql('ALTER TABLE champ CHANGE idx idx INT DEFAULT NULL');
        $this->addSql('ALTER TABLE champvaleurliste CHANGE idx idx INT DEFAULT NULL');
    }

    public function postDown(Schema $schema): void {
        $this->connection->executeStatement('UPDATE rubrique SET idx = NULL WHERE idx = -1');
        $this->connection->executeStatement('UPDATE champ SET idx = NULL WHERE idx = -1');
        $this->connection->executeStatement('UPDATE champvaleurliste SET idx = NULL WHERE idx = -1');
    }

}
