<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220142015 extends AbstractMigration
{
    public function getDescription() : string
    {
        return "Création d'un utilisateur Plat'AU.";
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $queryBuilder = $this->connection->createQueryBuilder();
        $hasPlatauUserQuery = $queryBuilder->select('*')
            ->from('utilisateur', 'u')
            ->where($queryBuilder->expr()->eq('u.USERNAME_UTILISATEUR', ':username'))
            ->setParameter('username', 'platau')
            ->execute()
        ;
        $hasPlatauUser = $hasPlatauUserQuery->fetchAssociative();

        $this->skipIf([] !== $hasPlatauUser, "Utilisateur Plat'AU déjà existant.");

        $this->addSql("INSERT INTO `utilisateurinformations`(`NOM_UTILISATEURINFORMATIONS`, `ID_FONCTION`) VALUES('Service Plat''AU', 99)");
        $this->addSql("INSERT INTO `utilisateur`(`USERNAME_UTILISATEUR`, `ID_UTILISATEURINFORMATIONS`, `ID_GROUPE`) VALUES('platau', (SELECT `ID_UTILISATEURINFORMATIONS` from `utilisateurinformations` where `NOM_UTILISATEURINFORMATIONS` = 'Service Plat''AU'), 1)");
        $this->addSql("INSERT INTO `utilisateurpreferences`(`ID_UTILISATEUR`, `DASHBOARD_BLOCS`) VALUES ((SELECT `ID_UTILISATEUR` from `utilisateur` where `USERNAME_UTILISATEUR` = 'platau'), NULL)");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $queryBuilder = $this->connection->createQueryBuilder();
        $hasPlatauUserQuery = $queryBuilder->select('*')
            ->from('utilisateur', 'u')
            ->where($queryBuilder->expr()->eq('u.USERNAME_UTILISATEUR', ':username'))
            ->setParameter('username', 'platau')
            ->execute()
        ;
        $hasPlatauUser = $hasPlatauUserQuery->fetchAssociative();

        $this->skipIf([] === $hasPlatauUser, "Utilisateur Plat'AU non existant.");

        $this->addSql("DELETE FROM `utilisateurpreferences` where `ID_UTILISATEUR` = (SELECT `ID_UTILISATEUR` from `utilisateur` where `USERNAME_UTILISATEUR` = 'platau')");
        $this->addSql("DELETE FROM `utilisateur` where `ID_UTILISATEURINFORMATIONS` = (SELECT `ID_UTILISATEURINFORMATIONS` from `utilisateurinformations` where `NOM_UTILISATEURINFORMATIONS` = 'Service Plat''AU')");
        $this->addSql("DELETE FROM `utilisateurinformations` where `NOM_UTILISATEURINFORMATIONS` = 'Service Plat''AU'");
    }
}
