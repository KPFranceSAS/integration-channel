<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220207132359 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE amazon_reimbursement (id INT AUTO_INCREMENT NOT NULL, original_reimbursement_id INT DEFAULT NULL, product_id INT NOT NULL, approval_date DATETIME NOT NULL, reimbursement_id INT NOT NULL, case_id INT DEFAULT NULL, amazon_order_id VARCHAR(255) DEFAULT NULL, reason VARCHAR(255) DEFAULT NULL, sku VARCHAR(255) DEFAULT NULL, fnsku VARCHAR(255) DEFAULT NULL, asin VARCHAR(255) DEFAULT NULL, condition_item VARCHAR(255) DEFAULT NULL, currency VARCHAR(255) NOT NULL, amount_per_unit DOUBLE PRECISION DEFAULT NULL, amount_total DOUBLE PRECISION DEFAULT NULL, amount_per_unit_currency DOUBLE PRECISION DEFAULT NULL, amount_total_currency DOUBLE PRECISION DEFAULT NULL, quantity_reimbursed_cash INT NOT NULL, quantity_reimbursed_inventory INT DEFAULT NULL, quantity_reimbursed_total INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_5140AD3BA2BDF391 (original_reimbursement_id), INDEX IDX_5140AD3B4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE amazon_reimbursement ADD CONSTRAINT FK_5140AD3BA2BDF391 FOREIGN KEY (original_reimbursement_id) REFERENCES amazon_reimbursement (id)');
        $this->addSql('ALTER TABLE amazon_reimbursement ADD CONSTRAINT FK_5140AD3B4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amazon_reimbursement DROP FOREIGN KEY FK_5140AD3BA2BDF391');
        $this->addSql('DROP TABLE amazon_reimbursement');
        $this->addSql('ALTER TABLE amazon_order CHANGE amazon_order_id amazon_order_id VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE merchant_order_id merchant_order_id VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE order_status order_status VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE fulfillment_channel fulfillment_channel VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE sales_channel sales_channel VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ship_service_level ship_service_level VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE sku sku VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE asin asin VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE item_status item_status VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE currency currency VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ship_city ship_city VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ship_postal_code ship_postal_code VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ship_state ship_state VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ship_country ship_country VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE promotion_ids promotion_ids VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE fulfilled_by fulfilled_by VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE integration_number integration_number VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE brand CHANGE name name VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE integration_file CHANGE document_number document_number VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE external_order_id external_order_id VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE profile_channel profile_channel VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE currency currency VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE product CHANGE sku sku VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE asin asin VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE comments comments LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE product_correlation CHANGE sku_used sku_used VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE sku_erp sku_erp VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE email email VARCHAR(180) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE password password VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE web_order CHANGE external_number external_number VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE order_erp order_erp VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE invoice_erp invoice_erp VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE channel channel VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE subchannel subchannel VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE warehouse warehouse VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE erp_document erp_document VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE company company VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE fulfilled_by fulfilled_by VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
