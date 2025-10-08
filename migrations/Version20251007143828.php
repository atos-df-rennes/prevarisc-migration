<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251007143828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table configurations.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE configurations (id INT AUTO_INCREMENT NOT NULL, prevarisc_commission_communale_type_id INT UNSIGNED DEFAULT NULL, prevarisc_debug_enabled TINYINT(1) DEFAULT NULL, prevarisc_enforce_security TINYINT(1) DEFAULT NULL, prevarisc_plugin_ignkey VARCHAR(255) DEFAULT NULL, prevarisc_local_sommeil_types VARCHAR(255) DEFAULT NULL, prevarisc_risques_naturels_exemples LONGTEXT DEFAULT NULL, prevarisc_risques_technologiques_exemples LONGTEXT DEFAULT NULL, prevarisc_dashboard_next_commissions_days INT DEFAULT NULL, prevarisc_dashboard_dossiers_sans_avis_days INT DEFAULT NULL, prevarisc_dashboard_courrier_sans_reponse_days INT DEFAULT NULL, prevarisc_date_commission_relance_periodicite TINYINT(1) DEFAULT NULL, prevarisc_descriptif_personnalise TINYINT(1) DEFAULT NULL, prevarisc_unite_periodicite_annees TINYINT(1) DEFAULT NULL, prevarisc_deactivate_platau TINYINT(1) DEFAULT NULL, prevarisc_dossiers_max_aafficher INT DEFAULT NULL, prevarisc_platau_no_healthcheck TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_31C6AD914F2301A (prevarisc_commission_communale_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE configurations ADD CONSTRAINT FK_31C6AD914F2301A FOREIGN KEY (prevarisc_commission_communale_type_id) REFERENCES commissiontype (ID_COMMISSIONTYPE)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE configurations');
    }
}
