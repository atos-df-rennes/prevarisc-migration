<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Sépare les privilèges de lecture et d'écriture des avis, dérogations et vérifications techniques.";
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE privileges SET name = 'lecture_avis_derogations', text = 'Lecture' WHERE name = 'avis_derogations'");
        $this->addSql("UPDATE privileges SET name = 'lecture_verifications_techniques', text = 'Lecture' WHERE name = 'verifications_techniques'");
        $this->addSql("INSERT INTO privileges (name, text, id_resource)
            SELECT 'ecriture_avis_derogations', 'Écriture', p.id_resource
            FROM privileges p
            WHERE p.name = 'lecture_avis_derogations'
              AND NOT EXISTS (SELECT 1 FROM privileges existing WHERE existing.name = 'ecriture_avis_derogations')");
        $this->addSql("INSERT INTO privileges (name, text, id_resource)
            SELECT 'ecriture_verifications_techniques', 'Écriture', p.id_resource
            FROM privileges p
            WHERE p.name = 'lecture_verifications_techniques'
              AND NOT EXISTS (SELECT 1 FROM privileges existing WHERE existing.name = 'ecriture_verifications_techniques')");
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->executeStatement("INSERT IGNORE INTO `groupe-privileges` (ID_GROUPE, id_privilege)
            SELECT gp.ID_GROUPE, write_privilege.id_privilege
            FROM `groupe-privileges` gp
            INNER JOIN privileges read_privilege ON read_privilege.id_privilege = gp.id_privilege
            INNER JOIN privileges write_privilege ON write_privilege.name = CASE read_privilege.name
                WHEN 'lecture_avis_derogations' THEN 'ecriture_avis_derogations'
                WHEN 'lecture_verifications_techniques' THEN 'ecriture_verifications_techniques'
            END
            WHERE read_privilege.name IN ('lecture_avis_derogations', 'lecture_verifications_techniques')");
    }

    public function preDown(Schema $schema): void
    {
        $this->connection->executeStatement("DELETE gp
            FROM `groupe-privileges` gp
            INNER JOIN privileges p ON p.id_privilege = gp.id_privilege
            WHERE p.name IN ('ecriture_avis_derogations', 'ecriture_verifications_techniques')");
    }

    public function down(Schema $schema): void
    {
        // Les modifications manuelles des droits d’écriture sont volontairement perdues au rollback.
        $this->addSql("DELETE FROM privileges WHERE name IN ('ecriture_avis_derogations', 'ecriture_verifications_techniques')");
        $this->addSql("UPDATE privileges SET name = 'avis_derogations', text = 'Lecture et écriture des avis et dérogations' WHERE name = 'lecture_avis_derogations'");
        $this->addSql("UPDATE privileges SET name = 'verifications_techniques', text = 'Lecture et écriture vérifications techniques' WHERE name = 'lecture_verifications_techniques'");
    }
}
