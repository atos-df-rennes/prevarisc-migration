<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250211124201 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Met à jour le schéma vers la dernière version Atos depuis la dernière version SDIS 62.';
    }

    // @todo: Vérifier si on peut pas mettre des steps pour indiquer où on en est (et faciliter en cas de rollback)
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        
        // 2.5-02
        if (!$schema->getTable('dossier')->hasColumn('DATESUPPRESSION_DOSSIER')) {
            $this->addSql("INSERT INTO `resources` (`id_resource`, `name`, `text`) VALUES (60, 'suppression', 'Suppression')");
            $this->addSql("INSERT INTO `privileges` (`id_privilege`, `name`, `text`, `id_resource`) VALUES
                (70, 'delete_dossier', 'Suppression des dossiers', 60),
                (71, 'delete_etablissement', 'Suppression des établissements', 60)
            ");
            $this->addSql("ALTER TABLE `dossier` ADD `DATESUPPRESSION_DOSSIER` DATE");
            $this->addSql("ALTER TABLE `etablissement` ADD `DATESUPPRESSION_ETABLISSEMENT` DATE");
        }

        // 2.5-32
        if (!$schema->hasTable('changement')) {
            $this->addSql("INSERT INTO `resources`(`id_resource`,`name`, `text`) VALUES (56,'alerte_email','Alertes')");
            $this->addSql("INSERT INTO `privileges`(`id_privilege`,`name`, `text`,`id_resource`) VALUES 
                (60,'alerte_statut','Changement de statut',56),
                (61,'alerte_avis','Changement d''avis',56),
                (62,'alerte_classement','Changement de classement',56)
            ");

            $this->addSql('CREATE TABLE changement (ID_CHANGEMENT INT UNSIGNED AUTO_INCREMENT NOT NULL, LIBELLE_CHANGEMENT VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, MESSAGE_CHANGEMENT TEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, PRIMARY KEY(ID_CHANGEMENT)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
            $this->addSql("INSERT INTO `changement` VALUES
                (1,'Changement de statut','<p>Bonjour,</p>\r\n<p>L\'&eacute;tablissement {etablissementNumeroId} {etablissementLibelle} est pass&eacute; au statut {etablissementStatut}.</p>\r\n<p>Bonne journ&eacute;e,</p>\r\n<p>Pr&eacute;varisc.</p>'),
                (2,'Changement d\'avis','<p>Bonjour,</p>\r\n<p>L\'&eacute;tablissement {etablissementNumeroId} {etablissementLibelle} est maintenant sous avis {etablissementAvis}.</p>\r\n<p>Bonne journ&eacute;e,</p>\r\n<p>Pr&eacute;varisc.</p>'),
                (3,'Changement de classement','<p>Bonjour,</p>\r\n<p>L\'&eacute;tablissement {etablissementNumeroId} {etablissementLibelle} est maintenant de cat&eacute;gorie {categorieEtablissement}, de type {typePrincipalEtablissement} - {activitePrincipaleEtablissement}.</p>\r\n<p>Bonne journ&eacute;e,</p>\r\n<p>Pr&eacute;varisc.</p>')
            ");
        }

        // 2.5-34
        if (!$schema->getTable('utilisateur')->hasColumn('FAILED_LOGIN_ATTEMPTS_UTILISATEUR')) {
            $this->addSql("ALTER TABLE `utilisateur` ADD `FAILED_LOGIN_ATTEMPTS_UTILISATEUR` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `ACTIF_UTILISATEUR`");
            $this->addSql("ALTER TABLE `utilisateur` ADD `IP_UTILISATEUR` VARCHAR(45) DEFAULT NULL AFTER `FAILED_LOGIN_ATTEMPTS_UTILISATEUR`");
        }

        // 2.5-37
        $this->addSql("INSERT IGNORE INTO `resources`(`id_resource`,`name`, `text`) VALUES (57,'search','Recherche')");
        $this->addSql("INSERT IGNORE INTO `privileges`(`id_privilege`,`name`, `text`,`id_resource`) VALUES 
            (63,'search_ets','Etablissements',57),
            (64,'search_dossiers','Dossiers',57)
        ");
        $this->addSql("INSERT IGNORE INTO `groupe-privileges`(`ID_GROUPE`, `id_privilege`) select id_groupe,63 from groupe");
        $this->addSql("INSERT IGNORE INTO `groupe-privileges`(`ID_GROUPE`, `id_privilege`) select id_groupe,64 from groupe");

        // 2.5-38
        $this->addSql("INSERT IGNORE INTO `privileges` (`id_privilege`, `name`, `text`, `id_resource`) VALUES (80, 'calendar_view_all', 'Voir tous les dossiers et commissions', 2)");

        // 2.5-39
        $this->addSql("INSERT IGNORE INTO `resources`(`id_resource`,`name`, `text`) VALUES(200,'export', 'Export Calc')");
        $this->addSql("INSERT IGNORE INTO `privileges`(`id_privilege`,`name`, `text`,`id_resource`) VALUES(200,'export_ets', 'Etablissements',200)");
        $this->addSql("INSERT IGNORE INTO `privileges`(`id_privilege`,`name`, `text`,`id_resource`) VALUES(210,'export_doss', 'Dossiers',200)");

        // 2.5-39 (bis)
        if (!$schema->getTable('etablissementinformations')->hasColumn('EFFECTIFCARAVANE__ETABLISSEMENTINFORMATIONS')) {
            $this->addSql("ALTER TABLE`etablissementinformations` ADD COLUMN `EFFECTIFCARAVANE__ETABLISSEMENTINFORMATIONS` int(11) NULL DEFAULT NULL");
            $this->addSql("ALTER TABLE`etablissementinformations` ADD COLUMN `EFFECTIFHABITATION_ETABLISSEMENTINFORMATIONS` int(11) NULL DEFAULT NULL");
            $this->addSql("ALTER TABLE`etablissementinformations` ADD COLUMN `EFFECTIFEMPLACEMENTNU_ETABLISSEMENTINFORMATIONS` int(11) NULL DEFAULT NULL");
            $this->addSql("ALTER TABLE`etablissementinformations` ADD COLUMN `EFFECTIFDIVERS_ETABLISSEMENTINFORMATIONS` int(11) NULL DEFAULT NULL");
        }

        // 2.5-40
        $this->addSql("UPDATE `dossiernatureliste` SET `ID_DOSSIERTYPE` = 1 WHERE `dossiernatureliste`.`ID_DOSSIERNATURE` = 19");

        // 2.5-41
        $this->addSql("UPDATE `typeactivite` SET `LIBELLE_ACTIVITE` = 'Salles polyvalentes non visées par le Type X (salle polyvalente qui n'a pas une destination unique)' WHERE ID_TYPEACTIVITE = 25");

        // 2.5-42
        if (!$schema->getTable('dossier')->hasColumn('ID_PLATAU')) {
            $this->addSql("ALTER TABLE `dossier` ADD `ID_PLATAU` CHAR(11) NULL DEFAULT NULL");
        }

        // 2.5-43
        $this->addSql("INSERT IGNORE INTO `privileges`(`id_privilege`,`name`, `text`,`id_resource`) VALUES(211,'view_doss_platau_sans_etab', 'Dossiers Plat\'AU à traiter',100)");

        // 2.5-44
        $queryBuilder = $this->connection->createQueryBuilder();
        $hasPlatauUserQuery = $queryBuilder->select('*')
            ->from('utilisateur', 'u')
            ->where($queryBuilder->expr()->eq('u.USERNAME_UTILISATEUR', ':username'))
            ->setParameter('username', 'platau')
            ->execute()
        ;
        $hasPlatauUser = $hasPlatauUserQuery->fetchAssociative();
        if ([] === $hasPlatauUser) {
            $this->addSql("INSERT INTO `utilisateurinformations`(`NOM_UTILISATEURINFORMATIONS`, `ID_FONCTION`) VALUES('Service Plat''AU', 99)");
            $this->addSql("INSERT INTO `utilisateur`(`USERNAME_UTILISATEUR`, `ID_UTILISATEURINFORMATIONS`, `ID_GROUPE`) VALUES('platau', (SELECT `ID_UTILISATEURINFORMATIONS` from `utilisateurinformations` where `NOM_UTILISATEURINFORMATIONS` = 'Service Plat''AU'), 1)");
            $this->addSql("INSERT INTO `utilisateurpreferences`(`ID_UTILISATEUR`, `DASHBOARD_BLOCS`) VALUES ((SELECT `ID_UTILISATEUR` from `utilisateur` where `USERNAME_UTILISATEUR` = 'platau'), NULL)");
        }

        // 2.5-45
        if (!$schema->getTable('couchecarto')->hasColumn('ORDRE_COUCHECARTO')) {
            $this->addSql("ALTER TABLE `couchecarto` ADD COLUMN `ORDRE_COUCHECARTO` int(2) DEFAULT NULL");
        }

        // 2.5-46
        if (!$schema->getTable('etablissementinformations')->hasColumn('R14320_ETABLISSEMENTINFORMATIONS')) {
            $this->addSql("ALTER TABLE `etablissementinformations` CHANGE `R12320_ETABLISSEMENTINFORMATIONS` `R14320_ETABLISSEMENTINFORMATIONS` tinyint(1)");
            $this->addSql("UPDATE `prescriptionarticleliste` SET `LIBELLE_ARTICLE` = 'R. 143-20' WHERE `LIBELLE_ARTICLE` LIKE 'R. 123-20'");
            $this->addSql("UPDATE `prescriptionarticleliste` SET `LIBELLE_ARTICLE` = 'R. 143-16' WHERE `LIBELLE_ARTICLE` LIKE 'R. 123-16'");
        }

        // 2.5-47
        if (!$schema->getTable('etablissement')->hasColumn('DELETED_BY')) {
            $this->addSql("ALTER TABLE etablissement ADD COLUMN `DELETED_BY` bigint(20) unsigned DEFAULT NULL");
            $this->addSql("ALTER TABLE dossier ADD COLUMN `DELETED_BY` bigint(20) unsigned DEFAULT NULL");
        }

        // 2.5-48
        $this->addSql("ALTER TABLE `etablissementadresse` MODIFY `LON_ETABLISSEMENTADRESSE` float(9,6)");
        $this->addSql("ALTER TABLE `etablissementadresse` MODIFY `LAT_ETABLISSEMENTADRESSE` float(8,6)");

        // Formulaires
        $this->addSql("CREATE TABLE IF NOT EXISTS `capsulerubrique` (
            `ID_CAPSULERUBRIQUE` bigint(20) NOT NULL AUTO_INCREMENT,
            `NOM_INTERNE` varchar(255) NOT NULL,
            `NOM` varchar(255) NOT NULL,
            PRIMARY KEY (`ID_CAPSULERUBRIQUE`)
        )");
        $this->addSql("INSERT IGNORE INTO `capsulerubrique` VALUES
            (1, 'descriptifEtablissement', 'Descriptif de l''établissement'),
            (2, 'descriptifVerificationsTechniques', 'Vérifications techniques du dossier'),
            (3, 'effectifsDegagementsEtablissement', 'Effectifs & Dégagements de l''établissement'),
            (4, 'effectifsDegagementsDossier', 'Effectifs & Dégagements du dossier')
        ");
        $this->addSql("CREATE TABLE IF NOT EXISTS `rubrique` (
            `ID_RUBRIQUE` bigint(20) NOT NULL AUTO_INCREMENT,
            `NOM` varchar(255) NOT NULL,
            `DEFAULT_DISPLAY` TINYINT(1) DEFAULT 0,
            `ID_CAPSULERUBRIQUE` bigint(20) NOT NULL,
            PRIMARY KEY (`ID_RUBRIQUE`),
            CONSTRAINT `fk_rubrique_capsuleRubrique` FOREIGN KEY (`ID_CAPSULERUBRIQUE`) REFERENCES `capsulerubrique` (`ID_CAPSULERUBRIQUE`) ON DELETE CASCADE ON UPDATE CASCADE
        )");
        $this->addSql("CREATE TABLE IF NOT EXISTS `listetypechamprubrique` (
            `ID_TYPECHAMP` bigint(20) NOT NULL AUTO_INCREMENT,
            `TYPE` varchar(50) NOT NULL,
            PRIMARY KEY (`ID_TYPECHAMP`)
        )");
        $this->addSql("INSERT IGNORE INTO `listetypechamprubrique` VALUES
            (1, 'Champ texte'),
            (2, 'Champ texte long'),
            (3, 'Liste'),
            (4, 'Numérique'),
            (5, 'Case à cocher'),
            (6, 'Parent'),
            (7, 'Date')
        ");
        $this->addSql("CREATE TABLE IF NOT EXISTS `champ` (
            `ID_CHAMP` bigint(20) NOT NULL AUTO_INCREMENT,
            `NOM` varchar(255) NOT NULL,
            `ID_TYPECHAMP` bigint(20) NOT NULL,
            `ID_RUBRIQUE` bigint(20) NOT NULL,
            PRIMARY KEY (`ID_CHAMP`),
            CONSTRAINT `fk_champ_listetypechamprubrique` FOREIGN KEY (`ID_TYPECHAMP`) REFERENCES `listetypechamprubrique` (`ID_TYPECHAMP`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk_champ_rubrique` FOREIGN KEY (`ID_RUBRIQUE`) REFERENCES `rubrique` (`ID_RUBRIQUE`) ON DELETE CASCADE ON UPDATE CASCADE
        )");
        $this->addSql("CREATE TABLE IF NOT EXISTS `champvaleurliste` (
            `ID_VALEURLISTE` bigint(20) NOT NULL AUTO_INCREMENT,
            `VALEUR` varchar(255) NOT NULL,
            `ID_CHAMP` bigint(20) NOT NULL,
            PRIMARY KEY (`ID_VALEURLISTE`),
            CONSTRAINT `fk_champvaleurliste_champ` FOREIGN KEY (`ID_CHAMP`) REFERENCES `champ` (`ID_CHAMP`) ON DELETE CASCADE ON UPDATE CASCADE
        )");
        $this->addSql("CREATE TABLE IF NOT EXISTS `valeur` (
            `ID_VALEUR` bigint(20) NOT NULL AUTO_INCREMENT,
            `VALEUR_STR` varchar(255) DEFAULT NULL,
            `VALEUR_LONG_STR` text DEFAULT NULL,
            `VALEUR_INT` int(10) DEFAULT NULL,
            `VALEUR_CHECKBOX` TINYINT(1) DEFAULT NULL,
            `ID_CHAMP` bigint(20) NOT NULL,
            PRIMARY KEY (`ID_VALEUR`),
            KEY `fk_valeur_champ_idx` (`ID_CHAMP`),
            CONSTRAINT `fk_valeur_champ` FOREIGN KEY (`ID_CHAMP`) REFERENCES `champ` (`ID_CHAMP`) ON DELETE CASCADE ON UPDATE CASCADE
        )");
        $this->addSql("INSERT IGNORE INTO `privileges`(`id_privilege`,`name`, `text`, `id_resource`) VALUES(212,'gestion_formulaires', 'Gestion des formulaires personnalisés', 1)");
        $this->addSql("CREATE TABLE IF NOT EXISTS `displayrubriqueetablissement` (
            `ID_ETABLISSEMENT` bigint(20) unsigned NOT NULL,
            `ID_RUBRIQUE` bigint(20) NOT NULL,
            `USER_DISPLAY` TINYINT(1) DEFAULT 0,
            PRIMARY KEY (`ID_ETABLISSEMENT`,`ID_RUBRIQUE`),
            KEY `fk_displayRubriqueEtablissement_etablissement_idx` (`ID_ETABLISSEMENT`),
            KEY `fk_displayRubriqueEtablissement_rubrique_idx` (`ID_RUBRIQUE`),
            CONSTRAINT `fk_displayRubriqueEtablissement_etablissement` FOREIGN KEY (`ID_ETABLISSEMENT`) REFERENCES `etablissement` (`ID_ETABLISSEMENT`) ON DELETE CASCADE ON UPDATE NO ACTION,
            CONSTRAINT `fk_displayRubriqueEtablissement_rubrique` FOREIGN KEY (`ID_RUBRIQUE`) REFERENCES `rubrique` (`ID_RUBRIQUE`) ON DELETE CASCADE ON UPDATE CASCADE
        )");
        $this->addSql("CREATE TABLE IF NOT EXISTS `etablissementvaleur` (
            `ID_ETABLISSEMENT` bigint(20) unsigned NOT NULL,
            `ID_VALEUR` bigint(20) NOT NULL,
            KEY `fk_etablissementValeur_dossier_idx` (`ID_ETABLISSEMENT`),
            KEY `fk_etablissementValeur_valeur_idx` (`ID_VALEUR`),
            CONSTRAINT `fk_etablissementValeur_etablissement` FOREIGN KEY (`ID_ETABLISSEMENT`) REFERENCES `etablissement` (`ID_ETABLISSEMENT`) ON DELETE CASCADE ON UPDATE NO ACTION,
            CONSTRAINT `fk_etablissementValeur_valeur` FOREIGN KEY (`ID_VALEUR`) REFERENCES `valeur` (`ID_VALEUR`) ON DELETE CASCADE ON UPDATE CASCADE
        )");
        $this->addSql("CREATE TABLE IF NOT EXISTS `displayrubriquedossier` (
            `ID_DOSSIER` bigint(20) NOT NULL,
            `ID_RUBRIQUE` bigint(20) NOT NULL,
            `USER_DISPLAY` TINYINT(1) DEFAULT 0,
            PRIMARY KEY (`ID_DOSSIER`,`ID_RUBRIQUE`),
            KEY `fk_displayRubriqueDossier_dossier_idx` (`ID_DOSSIER`),
            KEY `fk_displayRubriqueDossier_rubrique_idx` (`ID_RUBRIQUE`),
            CONSTRAINT `fk_displayRubriqueDossier_dossier` FOREIGN KEY (`ID_DOSSIER`) REFERENCES `dossier` (`ID_DOSSIER`) ON DELETE CASCADE ON UPDATE NO ACTION,
            CONSTRAINT `fk_displayRubriqueDossier_rubrique` FOREIGN KEY (`ID_RUBRIQUE`) REFERENCES `rubrique` (`ID_RUBRIQUE`) ON DELETE CASCADE ON UPDATE CASCADE
        )");
        $this->addSql("CREATE TABLE IF NOT EXISTS `dossiervaleur` (
            `ID_DOSSIER` bigint(20) NOT NULL,
            `ID_VALEUR` bigint(20) NOT NULL,
            KEY `fk_dossierValeur_dossier_idx` (`ID_DOSSIER`),
            KEY `fk_dossierValeur_valeur_idx` (`ID_VALEUR`),
            CONSTRAINT `fk_dossierValeur_dossier` FOREIGN KEY (`ID_DOSSIER`) REFERENCES `dossier` (`ID_DOSSIER`) ON DELETE CASCADE ON UPDATE NO ACTION,
            CONSTRAINT `fk_dossierValeur_valeur` FOREIGN KEY (`ID_VALEUR`) REFERENCES `valeur` (`ID_VALEUR`) ON DELETE CASCADE ON UPDATE CASCADE
        )");
        $this->addSql("INSERT IGNORE INTO `resources` (`id_resource`,`name`, `text`) VALUES (201,'verificationstechniques', 'Vérifications techniques')");
        $this->addSql("INSERT IGNORE INTO `privileges` (`id_privilege`,`name`, `text`, `id_resource`) VALUES (213,'verifications_techniques', 'Lecture et écriture vérifications techniques', 201)");
        
        if (!$schema->getTable('champ')->hasColumn('ID_PARENT')) {
            $this->addSql("ALTER TABLE `champ` ADD column `ID_PARENT` bigint(20)");
            $this->addSql("ALTER TABLE `champ`
                ADD CONSTRAINT `fk_ID_PARENT_CHAMP`
                FOREIGN KEY (`ID_PARENT`)
                REFERENCES `champ`(`ID_CHAMP`)
                ON DELETE CASCADE ON UPDATE NO ACTION
            ");
        }
        if (!$schema->getTable('champ')->hasColumn('idx')) {
            $this->addSql("ALTER TABLE `champ` ADD column idx INTEGER");
            $this->addSql("ALTER TABLE `rubrique` ADD column idx INTEGER");
        }

        $this->addSql("CREATE TABLE IF NOT EXISTS `avisderogations` (
            `ID_AVIS_DEROGATION` BIGINT(20) NOT NULL AUTO_INCREMENT,
            `TYPE` VARCHAR(50) NOT NULL,
            `TITRE` VARCHAR(50) NOT NULL,
            `INFORMATIONS` TEXT DEFAULT NULL,
            `AVIS` INT(1) UNSIGNED DEFAULT NULL,
            `ID_DOSSIER_LIE` BIGINT(20) DEFAULT NULL,
            `DISPLAY_HISTORIQUE` TINYINT DEFAULT 0,
            `ID_DOSSIER` BIGINT(20) NOT NULL,
            PRIMARY KEY (`ID_AVIS_DEROGATION`),
            KEY `fk_avisderogation_avis1_idx` (`AVIS`),
            KEY `fk_avisderogation_dossierlie1_idx` (`ID_DOSSIER_LIE`),
            KEY `fk_avisderogation_dossier1_idx` (`ID_DOSSIER`),
            CONSTRAINT `fk_avisderogation_avis1` FOREIGN KEY (`AVIS`) REFERENCES `avis` (`ID_AVIS`) ON DELETE NO ACTION ON UPDATE CASCADE,
            CONSTRAINT `fk_avisderogation_dossierlie1_idx` FOREIGN KEY (`ID_DOSSIER_LIE`) REFERENCES `dossier` (`ID_DOSSIER`) ON DELETE NO ACTION ON UPDATE CASCADE,
            CONSTRAINT `fk_avisderogation_dossier1_idx` FOREIGN KEY (`ID_DOSSIER`) REFERENCES `dossier` (`ID_DOSSIER`) ON DELETE CASCADE ON UPDATE CASCADE
        )");
        $this->addSql("INSERT IGNORE INTO `resources`(`id_resource`,`name`, `text`) VALUES(202,'avisderogations', 'Avis et dérogations')");
        $this->addSql("INSERT IGNORE INTO `privileges` (`id_privilege`,`name`, `text`, `id_resource`) VALUES (214,'avis_derogations', 'Lecture et écriture des avis et dérogations', 202)");
        $this->addSql("INSERT IGNORE INTO `resources`(`id_resource`,`name`, `text`) VALUES(203,'effectifs_degagements', 'Effectifs et Dégagements')");
        $this->addSql("INSERT IGNORE INTO `privileges` (`id_privilege`,`name`, `text`, `id_resource`) VALUES (215,'effectifs_degagements_ets', 'Etablissements', 203)");
        $this->addSql("INSERT IGNORE INTO `privileges` (`id_privilege`,`name`, `text`, `id_resource`) VALUES (216,'effectifs_degagements_doss', 'Dossiers', 203)");
        
        if ($schema->getTable('avisderogations')->hasColumn('INFORMATIONS')) {
            $this->addSql("ALTER TABLE `avisderogations` DROP COLUMN `INFORMATIONS`");

            $this->addSql("ALTER TABLE `avisderogations` ADD COLUMN `OBJET` TEXT DEFAULT NULL AFTER `TITRE`");
            $this->addSql("ALTER TABLE `avisderogations` ADD COLUMN `JUSTIFICATION` TEXT DEFAULT NULL AFTER `OBJET`");
            $this->addSql("ALTER TABLE `avisderogations` ADD COLUMN `ARTICLES_CONCERNES` TEXT DEFAULT NULL AFTER `JUSTIFICATION`");
            $this->addSql("ALTER TABLE `avisderogations` ADD COLUMN `MESURES_COMPENSATOIRES_PROPOSEES` TEXT DEFAULT NULL AFTER `ARTICLES_CONCERNES`");
            $this->addSql("ALTER TABLE `avisderogations` ADD COLUMN `ANALYSE_DE_RISQUE` TEXT DEFAULT NULL AFTER `MESURES_COMPENSATOIRES_PROPOSEES`");
        }

        if (!$schema->getTable('champ')->hasColumn('tableau')) {
            $this->addSql("ALTER TABLE `champ` ADD column `tableau` BOOLEAN");
            $this->addSql("ALTER TABLE `valeur` ADD column `idx` INTEGER");
        }

        // 2.5-49
        $this->addSql("ALTER TABLE `avisderogations` MODIFY `TITRE` VARCHAR(100)");

        // 2.5-50
        $this->addSql("DROP TABLE IF EXISTS `effectifdegagement`");
        $this->addSql("DROP TABLE IF EXISTS `dossiereffectifdegagement`");
        $this->addSql("DROP TABLE IF EXISTS `etablissementeffectifdegagement`");

        // 2.5-51
        $this->addSql("update `etablissementinformations` as `dest`,
            (
                select
                    eia.ID_ETABLISSEMENTINFORMATIONS as ID_ETAB_CELLULE,
                    eia2.ID_CATEGORIE as CATEGORIE_ETAB
                from etablissementinformationsactuel eia
                inner join etablissementlie el on eia.ID_ETABLISSEMENT = el.ID_FILS_ETABLISSEMENT
                left join etablissementinformationsactuel eia2 on eia2.ID_ETABLISSEMENT = el.ID_ETABLISSEMENT
                where eia.ID_GENRE = 3
                and eia2.ID_GENRE = 2
                and eia.ID_CATEGORIE <> eia2.ID_CATEGORIE
            ) as `src`
            set dest.ID_CATEGORIE = src.CATEGORIE_ETAB
            where dest.ID_ETABLISSEMENTINFORMATIONS = src.ID_ETAB_CELLULE
        ");

        // 2.5-52
        $this->addSql("UPDATE `listetypechamprubrique` SET `TYPE` = 'Texte' WHERE `ID_TYPECHAMP` = 1");
        $this->addSql("UPDATE `listetypechamprubrique` SET `TYPE` = 'Texte long' WHERE `ID_TYPECHAMP` = 2");

        if (!$schema->getTable('valeur')->hasColumn('VALEUR_DATE')) {
            $this->addSql("ALTER TABLE `valeur` ADD column `VALEUR_DATE` DATE DEFAULT NULL AFTER `VALEUR_CHECKBOX`");
        }

        // Plat'AU
        $this->addSql("CREATE TABLE IF NOT EXISTS `piecejointestatut` (
                `ID_PIECEJOINTESTATUT` int(20) NOT NULL AUTO_INCREMENT,
                `NOM_STATUT` varchar(50),
                PRIMARY KEY (`ID_PIECEJOINTESTATUT`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ");
        $this->addSql("INSERT IGNORE INTO `piecejointestatut`(`ID_PIECEJOINTESTATUT`,`NOM_STATUT`) VALUES
            (1,'not_exported'),
            (2,'to_be_exported'),
            (3,'exported'),
            (4,'on_error')
        ");

        if (!$schema->getTable('piecejointe')->hasColumn('ID_PIECEJOINTESTATUT')) {
            $this->addSql("ALTER TABLE `piecejointe` ADD COLUMN `ID_PIECEJOINTESTATUT` int(20) DEFAULT 1");
            $this->addSql("ALTER TABLE `piecejointe` ADD CONSTRAINT `fk_piecejointe_piecejointestatut1` FOREIGN KEY (`ID_PIECEJOINTESTATUT`) REFERENCES `piecejointestatut` (`ID_PIECEJOINTESTATUT`) ON DELETE SET NULL ON UPDATE CASCADE");
        }

        $this->addSql("CREATE TABLE IF NOT EXISTS `platauconsultation` (
                `ID_PLATAU` char(11),
                `STATUT_AVIS` varchar(50) DEFAULT NULL,
                `DATE_AVIS` date DEFAULT NULL,
                `STATUT_PEC` varchar(50) DEFAULT NULL,
                `DATE_PEC` date DEFAULT NULL,
                PRIMARY KEY (`ID_PLATAU`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ");

        // 2.5-53
        if (!$schema->getTable('platauconsultation')->hasColumn('DATE_REPONSE_ATTENDUE')) {
            $this->addSql("ALTER TABLE `platauconsultation` ADD column `DATE_REPONSE_ATTENDUE` date DEFAULT NULL");
        }

        // 2.5-54
        if (!$schema->getTable('prescriptiondossier')->hasColumn('DATE_LEVEE')) {
            $this->addSql("ALTER TABLE `prescriptiondossier` ADD COLUMN `DATE_LEVEE` DATE DEFAULT NULL");
            $this->addSql("ALTER TABLE `prescriptiondossier` ADD COLUMN `JUSTIFICATIF_LEVEE` TEXT DEFAULT NULL");
        }

        // 2.5-55
        if (!$schema->getTable('piecejointe')->hasColumn('ID_PLATAU')) {
            $this->addSql("ALTER TABLE `piecejointe` ADD COLUMN `ID_PLATAU` CHAR(11) NULL DEFAULT NULL");
            $this->addSql("ALTER TABLE `piecejointe` ADD COLUMN `MESSAGE_ERREUR` VARCHAR(255) NULL DEFAULT NULL");
        }

        if (!$schema->getTable('piecejointestatut')->hasColumn('NOM_LISIBLE')) {
            $this->addSql("ALTER TABLE `piecejointestatut` ADD COLUMN `NOM_LISIBLE` VARCHAR(50) NOT NULL");
            $this->addSql("INSERT IGNORE INTO `piecejointestatut` VALUES (5, 'awaiting_status', 'Vérification du statut')");
            $this->addSql("UPDATE `piecejointestatut` pjs JOIN (
                    SELECT 1 as ID_PIECEJOINTESTATUT, 'Non envoyé' as new_name
                    UNION ALL
                    SELECT 2, 'En attente d''envoi'
                    UNION ALL
                    SELECT 3, 'Envoyé'
                    UNION ALL
                    SELECT 4, 'Erreur lors de l''envoi'
                ) vals ON pjs.ID_PIECEJOINTESTATUT = vals.ID_PIECEJOINTESTATUT
                SET `NOM_LISIBLE` = new_name
            ");
        }

        // 2.5-56
        if (!$schema->getTable('dossier')->hasColumn('DATE_NOTIFICATION')) {
            $this->addSql("ALTER TABLE `dossier` ADD COLUMN `DATE_NOTIFICATION` DATETIME NULL DEFAULT NULL");
            $this->addSql("ALTER TABLE `piecejointe` ADD COLUMN `DATE_NOTIFICATION` DATETIME NULL DEFAULT NULL");
            $this->addSql("ALTER TABLE `utilisateur` ADD COLUMN `DERNIERE_VISITE_DASHBOARD` DATETIME DEFAULT CURRENT_TIMESTAMP");
            $this->addSql("ALTER TABLE `utilisateur` ADD COLUMN `DERNIERE_VISITE_PIECES_DOSSIER` DATETIME DEFAULT CURRENT_TIMESTAMP");
        }

        // 2.5-57
        if (!$schema->getTable('prescriptiondossier')->hasColumn('ID_DOSSIER_REPRISE')) {
            $this->addSql("ALTER TABLE `prescriptiondossier`
                ADD COLUMN `ID_DOSSIER_REPRISE` bigint(20),
                ADD KEY `fk_prescriptiondossier_dossierreprise_idx` (`ID_DOSSIER_REPRISE`),
                ADD CONSTRAINT `fk_prescriptiondossier_dossierreprise_idx` FOREIGN KEY (`ID_DOSSIER_REPRISE`) REFERENCES `dossier` (`ID_DOSSIER`) ON DELETE CASCADE ON UPDATE NO ACTION
            ");
        }

        // 2.5-58
        $this->addSql("INSERT IGNORE INTO `privileges` (`id_privilege`,`name`, `text`, `id_resource`) VALUES (217,'view_doss_platau_pjs_en_erreur', 'Dossiers Plat''AU pièces jointes non envoyées', 100)");

        // 2.5-59
        $this->addSql("INSERT IGNORE INTO `privileges` (`id_privilege`,`name`, `text`, `id_resource`) VALUES (218,'view_doss_platau_nouvelles_pjs', 'Dossiers Plat''AU nouvelles pièces jointes', 100)");

        // 2.6-01
        if (!$schema->getTable('piecejointe')->hasColumn('TYPE')) {
            $this->addSql("ALTER TABLE `piecejointe` ADD COLUMN `TYPE` VARCHAR(255) NULL DEFAULT NULL");
            $this->addSql("ALTER TABLE `piecejointe` ADD COLUMN `SOUS_TYPE` VARCHAR(255) NULL DEFAULT NULL");
            $this->addSql("ALTER TABLE `piecejointe` ADD COLUMN `NATURE` VARCHAR(255) NULL DEFAULT NULL");
            $this->addSql("ALTER TABLE `piecejointe` ADD COLUMN `DATE_DEPOT` DATE NULL DEFAULT NULL");
        }

        // 2.6-02
        $this->addSql("UPDATE `genre` SET `LIBELLE_GENRE` = 'BUP' WHERE `LIBELLE_GENRE` = 'EIC'");
        $this->addSql("UPDATE `resources` 
            SET `name` = REPLACE(name, 'eic', 'bup'), `text` = REPLACE(text, 'EIC', 'BUP')
            WHERE `name` LIKE 'etablissement_eic%'
        ");

        // On met les vues à la fin pour que les tables de référence soient à jour
        // 2.5-35
        $this->addSql("DROP VIEW IF EXISTS `etablissementinformationsactuel`");
        $this->addSql("CREATE VIEW `etablissementinformationsactuel` AS 
            SELECT * FROM `etablissementinformations` ei WHERE ei.DATE_ETABLISSEMENTINFORMATIONS = 
            ( 
                SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations 
                WHERE etablissementinformations.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT 
            )
        ");

        // 2.5-36
        $this->addSql("DROP VIEW IF EXISTS `dossierdernierevisite`");
        $this->addSql("CREATE VIEW `dossierdernierevisite` AS 
            SELECT 
                ed.ID_ETABLISSEMENT,
                MAX(d.DATEVISITE_DOSSIER) as DATEVISITE_DOSSIER,
                DATE_ADD(MAX(d.DATEVISITE_DOSSIER), INTERVAL ei.PERIODICITE_ETABLISSEMENTINFORMATIONS MONTH) as DATEPROCHAINEVISITE_DOSSIER
            FROM dossier d
                INNER JOIN dossiernature n on d.ID_DOSSIER = n.ID_DOSSIER
                INNER JOIN etablissementdossier ed on ed.ID_DOSSIER = d.ID_DOSSIER
                INNER JOIN etablissementinformationsactuel ei on ei.ID_ETABLISSEMENT = ed.ID_ETABLISSEMENT
            WHERE 
                n.ID_NATURE IN(26,21,47,48)
                AND d.AVIS_DOSSIER_COMMISSION IS NOT NULL
                AND d.AVIS_DOSSIER_COMMISSION > 0
                AND d.DATESUPPRESSION_DOSSIER IS NULL
            GROUP BY ed.ID_ETABLISSEMENT
        ");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        // 2.5-02
        if ($schema->getTable('dossier')->hasColumn('DATESUPPRESSION_DOSSIER')) {
            $this->addSql("ALTER TABLE `etablissement` DROP COLUMN `DATESUPPRESSION_ETABLISSEMENT`");
            $this->addSql("ALTER TABLE `dossier` DROP COLUMN `DATESUPPRESSION_DOSSIER`");
            $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` IN (70, 71)");
            $this->addSql("DELETE FROM `resources` WHERE `id_resource` = 60");
        }

        // 2.5-32
        if ($schema->hasTable('changement')) {
            $this->addSql("DROP TABLE `changement`");

            $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` IN (60, 61, 62)");
            $this->addSql("DELETE FROM `resources` WHERE `id_resource` = 56");
        }

        // 2.5-34
        if ($schema->getTable('utilisateur')->hasColumn('FAILED_LOGIN_ATTEMPTS_UTILISATEUR')) {
            $this->addSql("ALTER TABLE `utilisateur` DROP COLUMN `IP_UTILISATEUR`");
            $this->addSql("ALTER TABLE `utilisateur` DROP COLUMN `FAILED_LOGIN_ATTEMPTS_UTILISATEUR`");
        }

        // 2.5-37
        $this->addSql("DELETE FROM `groupe-privileges` WHERE `id_privilege` IN (63, 64)");
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` IN (63, 64)");
        $this->addSql("DELETE FROM `resources` WHERE `id_resource` = 57");

        // 2.5-38
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` = 80");

        // 2.5-39
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` = 200");
        $this->addSql("DELETE FROM `resources` WHERE `id_resource` = 200");
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` = 210");

        // 2.5-39 (bis)
        if ($schema->getTable('etablissementinformations')->hasColumn('EFFECTIFCARAVANE__ETABLISSEMENTINFORMATIONS')) {
            $this->addSql("ALTER TABLE`etablissementinformations` DROP COLUMN `EFFECTIFCARAVANE__ETABLISSEMENTINFORMATIONS`");
            $this->addSql("ALTER TABLE`etablissementinformations` DROP COLUMN `EFFECTIFHABITATION_ETABLISSEMENTINFORMATIONS`");
            $this->addSql("ALTER TABLE`etablissementinformations` DROP COLUMN `EFFECTIFEMPLACEMENTNU_ETABLISSEMENTINFORMATIONS`");
            $this->addSql("ALTER TABLE`etablissementinformations` DROP COLUMN `EFFECTIFDIVERS_ETABLISSEMENTINFORMATIONS`");
        }

        // 2.5-42
        if ($schema->getTable('dossier')->hasColumn('ID_PLATAU')) {
            $this->addSql("ALTER TABLE `dossier` DROP COLUMN `ID_PLATAU`");
        }

        // 2.5-43
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` = 211");

        // 2.5-44
        $this->addSql("DELETE FROM `utilisateurpreferences` where `ID_UTILISATEUR` = (SELECT `ID_UTILISATEUR` from `utilisateur` where `USERNAME_UTILISATEUR` = 'platau')");
        $this->addSql("DELETE FROM `utilisateur` where `ID_UTILISATEURINFORMATIONS` = (SELECT `ID_UTILISATEURINFORMATIONS` from `utilisateurinformations` where `NOM_UTILISATEURINFORMATIONS` = 'Service Plat''AU')");
        $this->addSql("DELETE FROM `utilisateurinformations` where `NOM_UTILISATEURINFORMATIONS` = 'Service Plat''AU'");

        // 2.5-45
        if ($schema->getTable('couchecarto')->hasColumn('ORDRE_COUCHECARTO')) {
            $this->addSql("ALTER TABLE `couchecarto` DROP COLUMN `ORDRE_COUCHECARTO`");
        }

        // 2.5-46
        if ($schema->getTable('etablissementinformations')->hasColumn('R14320_ETABLISSEMENTINFORMATIONS')) {
            $this->addSql("ALTER TABLE `etablissementinformations` CHANGE `R14320_ETABLISSEMENTINFORMATIONS` `R12320_ETABLISSEMENTINFORMATIONS` tinyint(1)");
            $this->addSql("UPDATE `prescriptionarticleliste` SET `LIBELLE_ARTICLE` = 'R. 123-20' WHERE `LIBELLE_ARTICLE` LIKE 'R. 143-20'");
            $this->addSql("UPDATE `prescriptionarticleliste` SET `LIBELLE_ARTICLE` = 'R. 123-16' WHERE `LIBELLE_ARTICLE` LIKE 'R. 143-16'");
        }

        // 2.5-47
        if ($schema->getTable('etablissement')->hasColumn('DELETED_BY')) {
            $this->addSql("ALTER TABLE etablissement DROP COLUMN `DELETED_BY`");
            $this->addSql("ALTER TABLE dossier DROP COLUMN `DELETED_BY`");
        }

        // 2.5-48
        $this->addSql("ALTER TABLE `etablissementadresse` MODIFY `LON_ETABLISSEMENTADRESSE` float");
        $this->addSql("ALTER TABLE `etablissementadresse` MODIFY `LAT_ETABLISSEMENTADRESSE` float");

        // Formulaires
        $this->addSql("DROP TABLE IF EXISTS `capsulerubrique`");
        $this->addSql("DROP TABLE IF EXISTS `rubrique`");
        $this->addSql("DROP TABLE IF EXISTS `listetypechamprubrique`");
        $this->addSql("DROP TABLE IF EXISTS `champ`");
        $this->addSql("DROP TABLE IF EXISTS `champvaleurliste`");
        $this->addSql("DROP TABLE IF EXISTS `valeur`");
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` = 212");
        $this->addSql("DROP TABLE IF EXISTS `displayrubriqueetablissement`");
        $this->addSql("DROP TABLE IF EXISTS `etablissementvaleur`");
        $this->addSql("DROP TABLE IF EXISTS `displayrubriquedossier`");
        $this->addSql("DROP TABLE IF EXISTS `dossiervaleur`");
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` = 213");
        $this->addSql("DELETE FROM `resources` WHERE `id_privilege` = 201");
        $this->addSql("DROP TABLE IF EXISTS `avisderogations`");
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` = 214");
        $this->addSql("DELETE FROM `resources` WHERE `id_privilege` = 202");
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` = 215");
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` = 216");
        $this->addSql("DELETE FROM `resources` WHERE `id_privilege` = 203");

        // Plat'AU
        $this->addSql("DROP TABLE IF EXISTS `piecejointestatut`");
        
        if ($schema->getTable('piecejointe')->hasColumn('ID_PIECEJOINTESTATUT')) {
            $this->addSql("ALTER TABLE `piecejointe` DROP CONSTRAINT `fk_piecejointe_piecejointestatut1`");
            $this->addSql("ALTER TABLE `piecejointe` DROP COLUMN `ID_PIECEJOINTESTATUT`");
        }

        $this->addSql("DROP TABLE IF EXISTS `platauconsultation`");

        // 2.5-54
        if ($schema->getTable('prescriptiondossier')->hasColumn('DATE_LEVEE')) {
            $this->addSql("ALTER TABLE `prescriptiondossier` DROP COLUMN `DATE_LEVEE`");
            $this->addSql("ALTER TABLE `prescriptiondossier` DROP COLUMN `JUSTIFICATIF_LEVEE`");
        }

        // 2.5-55
        if ($schema->getTable('piecejointe')->hasColumn('ID_PLATAU')) {
            $this->addSql("ALTER TABLE `piecejointe` DROP COLUMN `ID_PLATAU`");
            $this->addSql("ALTER TABLE `piecejointe` DROP COLUMN `MESSAGE_ERREUR`");
        }

        // 2.5-56
        if ($schema->getTable('dossier')->hasColumn('DATE_NOTIFICATION')) {
            $this->addSql("ALTER TABLE `dossier` DROP COLUMN `DATE_NOTIFICATION`");
            $this->addSql("ALTER TABLE `piecejointe` DROP COLUMN `DATE_NOTIFICATION`");
            $this->addSql("ALTER TABLE `utilisateur` DROP COLUMN `DERNIERE_VISITE_DASHBOARD`");
            $this->addSql("ALTER TABLE `utilisateur` DROP COLUMN `DERNIERE_VISITE_PIECES_DOSSIER`");
        }

        // 2.5-57
        if ($schema->getTable('prescriptiondossier')->hasColumn('ID_DOSSIER_REPRISE')) {
            $this->addSql("ALTER TABLE `prescriptiondossier` DROP COLUMN `ID_DOSSIER_REPRISE`");
        }

        // 2.5-58
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` = 217");

        //2.5-59
        $this->addSql("DELETE FROM `privileges` WHERE `id_privilege` = 218");

        // 2.6-01
        if ($schema->getTable('piecejointe')->hasColumn('TYPE')) {
            $this->addSql("ALTER TABLE `piecejointe` DROP COLUMN `TYPE`");
            $this->addSql("ALTER TABLE `piecejointe` DROP COLUMN `SOUS_TYPE`");
            $this->addSql("ALTER TABLE `piecejointe` DROP COLUMN `NATURE`");
            $this->addSql("ALTER TABLE `piecejointe` DROP COLUMN `DATE_DEPOT`");
        }

        // 2.6-02
        $this->addSql("UPDATE `genre` SET `LIBELLE_GENRE` = 'EIC' WHERE `LIBELLE_GENRE` = 'BUP'");
        $this->addSql("UPDATE `resources` 
            SET `name` = REPLACE(name, 'bup', 'eic'), `text` = REPLACE(text, 'BUP', 'EIC')
            WHERE `name` LIKE 'etablissement_bup%'
        ");

        // 2.5-35
        $this->addSql("DROP VIEW IF EXISTS `etablissementinformationsactuel`");

        // 2.5-36
        $this->addSql("DROP VIEW IF EXISTS `dossierdernierevisite`");
    }
}
