<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240913123656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE marketplace_invoice (id INT AUTO_INCREMENT NOT NULL, document_number VARCHAR(255) DEFAULT NULL, document_date DATETIME DEFAULT NULL, erp_document_number VARCHAR(255) DEFAULT NULL, vendor_number VARCHAR(255) NOT NULL, company VARCHAR(255) NOT NULL, total_amount_with_tax DOUBLE PRECISION NOT NULL, total_amount_tax DOUBLE PRECISION DEFAULT NULL, total_amount_no_tax DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', logs JSON DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE marketplace_invoice_line (id INT AUTO_INCREMENT NOT NULL, marketplace_invoice_id INT NOT NULL, description VARCHAR(255) NOT NULL, total_amount_with_tax DOUBLE PRECISION NOT NULL, total_amount_tax DOUBLE PRECISION DEFAULT NULL, total_amount_no_tax DOUBLE PRECISION DEFAULT NULL, INDEX IDX_D55D209DDFC97A9C (marketplace_invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE settlement (id INT AUTO_INCREMENT NOT NULL, posted_date DATETIME NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, number VARCHAR(255) DEFAULT NULL, total_amount DOUBLE PRECISION NOT NULL, total_commissions_with_tax DOUBLE PRECISION NOT NULL, total_refund_commisions_with_tax DOUBLE PRECISION NOT NULL, total_orders DOUBLE PRECISION NOT NULL, total_refunds DOUBLE PRECISION NOT NULL, total_subscriptions DOUBLE PRECISION NOT NULL, total_transfer DOUBLE PRECISION NOT NULL, channel VARCHAR(255) NOT NULL, status INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE settlement_transaction (id INT AUTO_INCREMENT NOT NULL, settlement_id INT NOT NULL, transaction_type VARCHAR(255) NOT NULL, amount DOUBLE PRECISION NOT NULL, reference_number VARCHAR(255) NOT NULL, comment VARCHAR(255) DEFAULT NULL, sku VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D36B97C9C2B9C425 (settlement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE marketplace_invoice_line ADD CONSTRAINT FK_D55D209DDFC97A9C FOREIGN KEY (marketplace_invoice_id) REFERENCES marketplace_invoice (id)');
        $this->addSql('ALTER TABLE settlement_transaction ADD CONSTRAINT FK_D36B97C9C2B9C425 FOREIGN KEY (settlement_id) REFERENCES settlement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketplace_invoice_line DROP FOREIGN KEY FK_D55D209DDFC97A9C');
        $this->addSql('ALTER TABLE settlement_transaction DROP FOREIGN KEY FK_D36B97C9C2B9C425');
        $this->addSql('DROP TABLE marketplace_invoice');
        $this->addSql('DROP TABLE marketplace_invoice_line');
        $this->addSql('DROP TABLE settlement');
        $this->addSql('DROP TABLE settlement_transaction');
    }
}
