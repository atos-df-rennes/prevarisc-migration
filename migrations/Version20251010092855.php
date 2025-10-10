<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251010092855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table des changements.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->hasTable('changement'), 'Table sur les changements déjà existante.');

        $this->addSql('CREATE TABLE changement (ID_CHANGEMENT INT UNSIGNED AUTO_INCREMENT NOT NULL, LIBELLE_CHANGEMENT VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, MESSAGE_CHANGEMENT TEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, PRIMARY KEY(ID_CHANGEMENT)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql("INSERT INTO `changement` VALUES
            (1,'Changement de statut','<p>Bonjour,</p>\r\n<p>L\'&eacute;tablissement {etablissementNumeroId} {etablissementLibelle} est pass&eacute; au statut {etablissementStatut}.</p>\r\n<p>Bonne journ&eacute;e,</p>\r\n<p>Pr&eacute;varisc.</p>'),
            (2,'Changement d\'avis','<p>Bonjour,</p>\r\n<p>L\'&eacute;tablissement {etablissementNumeroId} {etablissementLibelle} est maintenant sous avis {etablissementAvis}.</p>\r\n<p>Bonne journ&eacute;e,</p>\r\n<p>Pr&eacute;varisc.</p>'),
            (3,'Changement de classement','<p>Bonjour,</p>\r\n<p>L\'&eacute;tablissement {etablissementNumeroId} {etablissementLibelle} est maintenant de cat&eacute;gorie {categorieEtablissement}, de type {typePrincipalEtablissement} - {activitePrincipaleEtablissement}.</p>\r\n<p>Bonne journ&eacute;e,</p>\r\n<p>Pr&eacute;varisc.</p>')
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->hasTable('changement'), 'Table sur les changements non existante.');

        $this->addSql('DROP TABLE `changement`');
    }
}
