<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210618141611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE integration_file (id INT AUTO_INCREMENT NOT NULL, document_number VARCHAR(255) NOT NULL, document_type INT NOT NULL, external_order_id VARCHAR(255) NOT NULL, profile_channel VARCHAR(255) NOT NULL, currency VARCHAR(255) NOT NULL, total_amount DOUBLE PRECISION NOT NULL, total_vat DOUBLE PRECISION NOT NULL, total_vat_included DOUBLE PRECISION NOT NULL, date_updated DATETIME NOT NULL, channel_order_id INT NOT NULL, channel_adjustement_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE integration_file');
    }
}
