<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220154247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rafraichissement des vues.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP VIEW IF EXISTS `etablissementinformationsactuel`');
        $this->addSql('CREATE VIEW `etablissementinformationsactuel` AS 
            SELECT * FROM `etablissementinformations` ei WHERE ei.DATE_ETABLISSEMENTINFORMATIONS = 
            ( 
                SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations 
                WHERE etablissementinformations.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT 
            )
        ');

        $this->addSql('DROP VIEW IF EXISTS `dossierdernierevisite`');
        $this->addSql('CREATE VIEW `dossierdernierevisite` AS 
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
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
