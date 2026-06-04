<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du privilège view_doss_incomplets pour le bloc dossiers incomplets du tableau de bord.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $count = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('privileges')
            ->where('name = :name')
            ->setParameter('name', 'view_doss_incomplets')
            ->execute()
            ->fetchOne();
        $this->skipIf($count > 0, 'Privilège view_doss_incomplets déjà existant.');

        $this->addSql("INSERT INTO privileges (name, text, id_resource) VALUES ('view_doss_incomplets', 'Dossiers incomplets', 100)");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $count = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('privileges')
            ->where('name = :name')
            ->setParameter('name', 'view_doss_incomplets')
            ->execute()
            ->fetchOne();
        $this->skipIf(0 === $count, 'Privilège view_doss_incomplets déjà supprimé.');

        $this->addSql("DELETE FROM privileges WHERE name = 'view_doss_incomplets'");
    }
}
