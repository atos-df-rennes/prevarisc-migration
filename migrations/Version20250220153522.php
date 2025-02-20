<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220153522 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Reprise de prescription.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('prescriptiondossier')->hasColumn('ID_DOSSIER_REPRISE'), 'Reprise de prescription déjà existante.');

        $this->addSql("ALTER TABLE `prescriptiondossier`
            ADD COLUMN `ID_DOSSIER_REPRISE` bigint(20),
            ADD KEY `fk_prescriptiondossier_dossierreprise_idx` (`ID_DOSSIER_REPRISE`),
            ADD CONSTRAINT `fk_prescriptiondossier_dossierreprise_idx` FOREIGN KEY (`ID_DOSSIER_REPRISE`) REFERENCES `dossier` (`ID_DOSSIER`) ON DELETE CASCADE ON UPDATE NO ACTION
        ");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('prescriptiondossier')->hasColumn('ID_DOSSIER_REPRISE'), 'Reprise de prescription non existante.');

        $this->addSql("ALTER TABLE `prescriptiondossier` DROP FOREIGN KEY `fk_prescriptiondossier_dossierreprise_idx`");
        $this->addSql("ALTER TABLE `prescriptiondossier` DROP COLUMN `ID_DOSSIER_REPRISE`");
    }
}
