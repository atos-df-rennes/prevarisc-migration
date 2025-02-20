<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250219140426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des droits de suppression.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->skipIf($schema->getTable('dossier')->hasColumn('DATESUPPRESSION_DOSSIER'), 'Éléments de suppression déjà existants.');

        $this->addSql("INSERT INTO `resources` (`id_resource`, `name`, `text`) VALUES (60, 'suppression', 'Suppression')");
        $this->addSql("INSERT INTO `privileges` (`id_privilege`, `name`, `text`, `id_resource`) VALUES
            (70, 'delete_dossier', 'Suppression des dossiers', 60),
            (71, 'delete_etablissement', 'Suppression des établissements', 60)
        ");
        $this->addSql('ALTER TABLE `dossier` ADD `DATESUPPRESSION_DOSSIER` DATE');
        $this->addSql('ALTER TABLE `etablissement` ADD `DATESUPPRESSION_ETABLISSEMENT` DATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->skipIf(!$schema->getTable('dossier')->hasColumn('DATESUPPRESSION_DOSSIER'), 'Éléments de suppression non existants.');

        $this->addSql('ALTER TABLE `etablissement` DROP COLUMN `DATESUPPRESSION_ETABLISSEMENT`');
        $this->addSql('ALTER TABLE `dossier` DROP COLUMN `DATESUPPRESSION_DOSSIER`');
        $this->addSql('DELETE FROM `privileges` WHERE `id_privilege` IN (70, 71)');
        $this->addSql('DELETE FROM `resources` WHERE `id_resource` = 60');
    }
}
