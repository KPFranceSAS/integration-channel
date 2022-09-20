<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220920104156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_65FFC0A04584665A ON product_log_entry');
        $this->addSql('ALTER TABLE product_log_entry ADD product_sku VARCHAR(255) DEFAULT NULL, ADD sale_channel_id INT DEFAULT NULL, ADD sale_channel_name VARCHAR(255) DEFAULT NULL, CHANGE product_id product_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_log_entry DROP product_sku, DROP sale_channel_id, DROP sale_channel_name, CHANGE product_id product_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_65FFC0A04584665A ON product_log_entry (product_id)');
    }
}
