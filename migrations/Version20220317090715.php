<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220317090715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE amazon_financial_event (id INT AUTO_INCREMENT NOT NULL, event_group_id INT NOT NULL, product_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', transaction_type VARCHAR(255) NOT NULL, amazon_order_id VARCHAR(255) DEFAULT NULL, seller_order_id VARCHAR(255) DEFAULT NULL, adjustment_id VARCHAR(255) DEFAULT NULL, shipment_id VARCHAR(255) DEFAULT NULL, marketplace_name VARCHAR(255) DEFAULT NULL, amount_type VARCHAR(255) DEFAULT NULL, amount_description VARCHAR(255) DEFAULT NULL, amount DOUBLE PRECISION NOT NULL, amount_currency DOUBLE PRECISION DEFAULT NULL, posted_date DATETIME NOT NULL, order_item_code VARCHAR(255) DEFAULT NULL, sku VARCHAR(255) DEFAULT NULL, qty_purchased INT DEFAULT NULL, promotion_id VARCHAR(255) DEFAULT NULL, INDEX IDX_814A7CD6B8B83097 (event_group_id), INDEX IDX_814A7CD64584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE amazon_financial_event_group (id INT AUTO_INCREMENT NOT NULL, financial_event_id VARCHAR(255) NOT NULL, processing_status VARCHAR(255) NOT NULL, fund_transfert_status VARCHAR(255) DEFAULT NULL, fund_transfer_date DATETIME DEFAULT NULL, trace_identfier VARCHAR(255) DEFAULT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, original_total DOUBLE PRECISION DEFAULT NULL, converted_total DOUBLE PRECISION DEFAULT NULL, original_total_currency DOUBLE PRECISION DEFAULT NULL, converted_total_currency DOUBLE PRECISION DEFAULT NULL, beginning_balance DOUBLE PRECISION DEFAULT NULL, beginning_balance_currency DOUBLE PRECISION DEFAULT NULL, currency_code VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE amazon_financial_event ADD CONSTRAINT FK_814A7CD6B8B83097 FOREIGN KEY (event_group_id) REFERENCES amazon_financial_event_group (id)');
        $this->addSql('ALTER TABLE amazon_financial_event ADD CONSTRAINT FK_814A7CD64584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE web_order ADD customer_number VARCHAR(255) DEFAULT NULL, ADD tracking_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amazon_financial_event DROP FOREIGN KEY FK_814A7CD6B8B83097');
        $this->addSql('DROP TABLE amazon_financial_event');
        $this->addSql('DROP TABLE amazon_financial_event_group');
        $this->addSql('ALTER TABLE amazon_order CHANGE amazon_order_id amazon_order_id VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE merchant_order_id merchant_order_id VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE order_status order_status VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE fulfillment_channel fulfillment_channel VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE sales_channel sales_channel VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ship_service_level ship_service_level VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE sku sku VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE asin asin VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE item_status item_status VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE currency currency VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ship_city ship_city VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ship_postal_code ship_postal_code VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ship_state ship_state VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ship_country ship_country VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE promotion_ids promotion_ids VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE fulfilled_by fulfilled_by VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE integration_number integration_number VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE amazon_reimbursement CHANGE reimbursement_id reimbursement_id VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE case_id case_id VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE amazon_order_id amazon_order_id VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE reason reason VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE sku sku VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE fnsku fnsku VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE asin asin VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE condition_item condition_item VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE currency_unit currency_unit VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE amazon_return CHANGE order_id order_id VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE sku sku VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE asin asin VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE fnsku fnsku VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE fulfillment_center_id fulfillment_center_id VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE detailed_disposition detailed_disposition VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE reason reason VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE status status VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE license_plate_number license_plate_number VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE brand CHANGE name name VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE integration_file CHANGE document_number document_number VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE external_order_id external_order_id VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE profile_channel profile_channel VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE currency currency VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE product CHANGE sku sku VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE asin asin VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE comments comments LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE product_correlation CHANGE sku_used sku_used VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE sku_erp sku_erp VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE email email VARCHAR(180) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE password password VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE web_order DROP customer_number, DROP tracking_url, CHANGE external_number external_number VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE order_erp order_erp VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE invoice_erp invoice_erp VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE channel channel VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE subchannel subchannel VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE warehouse warehouse VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE erp_document erp_document VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE company company VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE fulfilled_by fulfilled_by VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
