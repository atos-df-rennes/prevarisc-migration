<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261120174617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Corrige les créateurs à 0 des dossiers Plat'AU et des autres dossiers.";
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        $platauUserIds = $this->connection->fetchFirstColumn(
            'SELECT ID_UTILISATEUR FROM utilisateur WHERE USERNAME_UTILISATEUR = :username LIMIT 2',
            ['username' => 'platau'],
        );
        $this->abortIf([] === $platauUserIds, "Compte de service Plat'AU « platau » introuvable.");
        $this->abortIf(1 !== \count($platauUserIds), "Plusieurs comptes de service Plat'AU « platau » trouvés.");
        $this->abortIf($platauUserIds[0] <= 0, "L'identifiant du compte de service Plat'AU doit être positif.");

        $this->addSql(
            'UPDATE dossier SET CREATEUR_DOSSIER = :creator WHERE CREATEUR_DOSSIER = 0 AND ID_PLATAU IS NOT NULL',
            ['creator' => $platauUserIds[0]],
        );
        $this->addSql('UPDATE dossier SET CREATEUR_DOSSIER = NULL WHERE CREATEUR_DOSSIER = 0 AND ID_PLATAU IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        $this->throwIrreversibleMigrationException('Impossible de distinguer les dossiers corrigés des dossiers qui avaient déjà ces valeurs.');
    }
}
