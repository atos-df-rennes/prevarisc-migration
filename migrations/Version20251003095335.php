<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251003095335 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Remplace le champ entier prevariscCommissionCommunaleType par une relation OneToOne vers CommissionType';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE configurations ADD prevarisc_commission_communale_type_id INT UNSIGNED DEFAULT NULL, DROP prevarisc_commission_communale_type');
        $this->addSql('ALTER TABLE configurations ADD CONSTRAINT FK_31C6AD914F2301A FOREIGN KEY (prevarisc_commission_communale_type_id) REFERENCES commissiontype (ID_COMMISSIONTYPE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_31C6AD914F2301A ON configurations (prevarisc_commission_communale_type_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE configurations ADD prevarisc_commission_communale_type INT DEFAULT NULL, DROP prevarisc_commission_communale_type_id');
    }
}
