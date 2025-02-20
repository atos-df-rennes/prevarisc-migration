<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220143138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Formulaires personnalisés.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS `capsulerubrique` (
            `ID_CAPSULERUBRIQUE` bigint(20) NOT NULL AUTO_INCREMENT,
            `NOM_INTERNE` varchar(255) NOT NULL,
            `NOM` varchar(255) NOT NULL,
            PRIMARY KEY (`ID_CAPSULERUBRIQUE`)
        )');
        $this->addSql("INSERT IGNORE INTO `capsulerubrique` VALUES
            (1, 'descriptifEtablissement', 'Descriptif de l''établissement'),
            (2, 'descriptifVerificationsTechniques', 'Vérifications techniques du dossier'),
            (3, 'effectifsDegagementsEtablissement', 'Effectifs & Dégagements de l''établissement'),
            (4, 'effectifsDegagementsDossier', 'Effectifs & Dégagements du dossier')
        ");
        $this->addSql('CREATE TABLE IF NOT EXISTS `rubrique` (
            `ID_RUBRIQUE` bigint(20) NOT NULL AUTO_INCREMENT,
            `NOM` varchar(255) NOT NULL,
            `DEFAULT_DISPLAY` TINYINT(1) DEFAULT 0,
            `ID_CAPSULERUBRIQUE` bigint(20) NOT NULL,
            PRIMARY KEY (`ID_RUBRIQUE`),
            CONSTRAINT `fk_rubrique_capsuleRubrique` FOREIGN KEY (`ID_CAPSULERUBRIQUE`) REFERENCES `capsulerubrique` (`ID_CAPSULERUBRIQUE`) ON DELETE CASCADE ON UPDATE CASCADE
        )');
        $this->addSql('CREATE TABLE IF NOT EXISTS `listetypechamprubrique` (
            `ID_TYPECHAMP` bigint(20) NOT NULL AUTO_INCREMENT,
            `TYPE` varchar(50) NOT NULL,
            PRIMARY KEY (`ID_TYPECHAMP`)
        )');
        $this->addSql("INSERT IGNORE INTO `listetypechamprubrique` VALUES
            (1, 'Champ texte'),
            (2, 'Champ texte long'),
            (3, 'Liste'),
            (4, 'Numérique'),
            (5, 'Case à cocher'),
            (6, 'Parent'),
            (7, 'Date')
        ");
        $this->addSql('CREATE TABLE IF NOT EXISTS `champ` (
            `ID_CHAMP` bigint(20) NOT NULL AUTO_INCREMENT,
            `NOM` varchar(255) NOT NULL,
            `ID_TYPECHAMP` bigint(20) NOT NULL,
            `ID_RUBRIQUE` bigint(20) NOT NULL,
            PRIMARY KEY (`ID_CHAMP`),
            CONSTRAINT `fk_champ_listetypechamprubrique` FOREIGN KEY (`ID_TYPECHAMP`) REFERENCES `listetypechamprubrique` (`ID_TYPECHAMP`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk_champ_rubrique` FOREIGN KEY (`ID_RUBRIQUE`) REFERENCES `rubrique` (`ID_RUBRIQUE`) ON DELETE CASCADE ON UPDATE CASCADE
        )');
        $this->addSql('CREATE TABLE IF NOT EXISTS `champvaleurliste` (
            `ID_VALEURLISTE` bigint(20) NOT NULL AUTO_INCREMENT,
            `VALEUR` varchar(255) NOT NULL,
            `ID_CHAMP` bigint(20) NOT NULL,
            PRIMARY KEY (`ID_VALEURLISTE`),
            CONSTRAINT `fk_champvaleurliste_champ` FOREIGN KEY (`ID_CHAMP`) REFERENCES `champ` (`ID_CHAMP`) ON DELETE CASCADE ON UPDATE CASCADE
        )');
        $this->addSql('CREATE TABLE IF NOT EXISTS `valeur` (
            `ID_VALEUR` bigint(20) NOT NULL AUTO_INCREMENT,
            `VALEUR_STR` varchar(255) DEFAULT NULL,
            `VALEUR_LONG_STR` text DEFAULT NULL,
            `VALEUR_INT` int(10) DEFAULT NULL,
            `VALEUR_CHECKBOX` TINYINT(1) DEFAULT NULL,
            `ID_CHAMP` bigint(20) NOT NULL,
            PRIMARY KEY (`ID_VALEUR`),
            KEY `fk_valeur_champ_idx` (`ID_CHAMP`),
            CONSTRAINT `fk_valeur_champ` FOREIGN KEY (`ID_CHAMP`) REFERENCES `champ` (`ID_CHAMP`) ON DELETE CASCADE ON UPDATE CASCADE
        )');
        $this->addSql("INSERT IGNORE INTO `privileges`(`id_privilege`,`name`, `text`, `id_resource`) VALUES(212,'gestion_formulaires', 'Gestion des formulaires personnalisés', 1)");
        $this->addSql('CREATE TABLE IF NOT EXISTS `displayrubriqueetablissement` (
            `ID_ETABLISSEMENT` bigint(20) unsigned NOT NULL,
            `ID_RUBRIQUE` bigint(20) NOT NULL,
            `USER_DISPLAY` TINYINT(1) DEFAULT 0,
            PRIMARY KEY (`ID_ETABLISSEMENT`,`ID_RUBRIQUE`),
            KEY `fk_displayRubriqueEtablissement_etablissement_idx` (`ID_ETABLISSEMENT`),
            KEY `fk_displayRubriqueEtablissement_rubrique_idx` (`ID_RUBRIQUE`),
            CONSTRAINT `fk_displayRubriqueEtablissement_etablissement` FOREIGN KEY (`ID_ETABLISSEMENT`) REFERENCES `etablissement` (`ID_ETABLISSEMENT`) ON DELETE CASCADE ON UPDATE NO ACTION,
            CONSTRAINT `fk_displayRubriqueEtablissement_rubrique` FOREIGN KEY (`ID_RUBRIQUE`) REFERENCES `rubrique` (`ID_RUBRIQUE`) ON DELETE CASCADE ON UPDATE CASCADE
        )');
        $this->addSql('CREATE TABLE IF NOT EXISTS `etablissementvaleur` (
            `ID_ETABLISSEMENT` bigint(20) unsigned NOT NULL,
            `ID_VALEUR` bigint(20) NOT NULL,
            KEY `fk_etablissementValeur_dossier_idx` (`ID_ETABLISSEMENT`),
            KEY `fk_etablissementValeur_valeur_idx` (`ID_VALEUR`),
            CONSTRAINT `fk_etablissementValeur_etablissement` FOREIGN KEY (`ID_ETABLISSEMENT`) REFERENCES `etablissement` (`ID_ETABLISSEMENT`) ON DELETE CASCADE ON UPDATE NO ACTION,
            CONSTRAINT `fk_etablissementValeur_valeur` FOREIGN KEY (`ID_VALEUR`) REFERENCES `valeur` (`ID_VALEUR`) ON DELETE CASCADE ON UPDATE CASCADE
        )');
        $this->addSql('CREATE TABLE IF NOT EXISTS `displayrubriquedossier` (
            `ID_DOSSIER` bigint(20) NOT NULL,
            `ID_RUBRIQUE` bigint(20) NOT NULL,
            `USER_DISPLAY` TINYINT(1) DEFAULT 0,
            PRIMARY KEY (`ID_DOSSIER`,`ID_RUBRIQUE`),
            KEY `fk_displayRubriqueDossier_dossier_idx` (`ID_DOSSIER`),
            KEY `fk_displayRubriqueDossier_rubrique_idx` (`ID_RUBRIQUE`),
            CONSTRAINT `fk_displayRubriqueDossier_dossier` FOREIGN KEY (`ID_DOSSIER`) REFERENCES `dossier` (`ID_DOSSIER`) ON DELETE CASCADE ON UPDATE NO ACTION,
            CONSTRAINT `fk_displayRubriqueDossier_rubrique` FOREIGN KEY (`ID_RUBRIQUE`) REFERENCES `rubrique` (`ID_RUBRIQUE`) ON DELETE CASCADE ON UPDATE CASCADE
        )');
        $this->addSql('CREATE TABLE IF NOT EXISTS `dossiervaleur` (
            `ID_DOSSIER` bigint(20) NOT NULL,
            `ID_VALEUR` bigint(20) NOT NULL,
            KEY `fk_dossierValeur_dossier_idx` (`ID_DOSSIER`),
            KEY `fk_dossierValeur_valeur_idx` (`ID_VALEUR`),
            CONSTRAINT `fk_dossierValeur_dossier` FOREIGN KEY (`ID_DOSSIER`) REFERENCES `dossier` (`ID_DOSSIER`) ON DELETE CASCADE ON UPDATE NO ACTION,
            CONSTRAINT `fk_dossierValeur_valeur` FOREIGN KEY (`ID_VALEUR`) REFERENCES `valeur` (`ID_VALEUR`) ON DELETE CASCADE ON UPDATE CASCADE
        )');
        $this->addSql("INSERT IGNORE INTO `resources` (`id_resource`,`name`, `text`) VALUES (201,'verificationstechniques', 'Vérifications techniques')");
        $this->addSql("INSERT IGNORE INTO `privileges` (`id_privilege`,`name`, `text`, `id_resource`) VALUES (213,'verifications_techniques', 'Lecture et écriture vérifications techniques', 201)");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM `privileges` WHERE `id_privilege` = 213');
        $this->addSql('DELETE FROM `resources` WHERE `id_privilege` = 201');
        $this->addSql('DROP TABLE IF EXISTS `dossiervaleur`');
        $this->addSql('DROP TABLE IF EXISTS `displayrubriquedossier`');
        $this->addSql('DROP TABLE IF EXISTS `etablissementvaleur`');
        $this->addSql('DROP TABLE IF EXISTS `displayrubriqueetablissement`');
        $this->addSql('DELETE FROM `privileges` WHERE `id_privilege` = 212');
        $this->addSql('DROP TABLE IF EXISTS `valeur`');
        $this->addSql('DROP TABLE IF EXISTS `champvaleurliste`');
        $this->addSql('DROP TABLE IF EXISTS `champ`');
        $this->addSql('DROP TABLE IF EXISTS `listetypechamprubrique`');
        $this->addSql('DROP TABLE IF EXISTS `rubrique`');
        $this->addSql('DROP TABLE IF EXISTS `capsulerubrique`');
    }
}
