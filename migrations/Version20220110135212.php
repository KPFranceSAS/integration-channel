<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220110135212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE amazon_order (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, brand_id INT DEFAULT NULL, amazon_order_id VARCHAR(255) NOT NULL, purchase_date DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, last_updated_date DATETIME NOT NULL, order_status VARCHAR(255) NOT NULL, fulfillment_channel VARCHAR(255) DEFAULT NULL, sales_channel VARCHAR(255) NOT NULL, ship_service_level VARCHAR(255) DEFAULT NULL, sku VARCHAR(255) NOT NULL, asin VARCHAR(255) NOT NULL, item_status VARCHAR(255) DEFAULT NULL, quantity INT NOT NULL, currency VARCHAR(255) DEFAULT NULL, ship_city VARCHAR(255) DEFAULT NULL, ship_postal_code VARCHAR(255) DEFAULT NULL, ship_state VARCHAR(255) DEFAULT NULL, ship_country VARCHAR(255) DEFAULT NULL, promotion_ids VARCHAR(255) DEFAULT NULL, fulfilled_by VARCHAR(255) DEFAULT NULL, item_price DOUBLE PRECISION DEFAULT NULL, item_tax DOUBLE PRECISION DEFAULT NULL, shipping_price DOUBLE PRECISION DEFAULT NULL, shipping_tax DOUBLE PRECISION DEFAULT NULL, gift_wrap_price DOUBLE PRECISION DEFAULT NULL, gift_wrap_tax DOUBLE PRECISION DEFAULT NULL, item_promotion_discount DOUBLE PRECISION DEFAULT NULL, ship_promotion_discount DOUBLE PRECISION DEFAULT NULL, vat_exclusive_item_price DOUBLE PRECISION DEFAULT NULL, vat_exclusive_shipping_price DOUBLE PRECISION DEFAULT NULL, vat_exclusive_giftwrap_price DOUBLE PRECISION DEFAULT NULL, gift_wrap_price_currency DOUBLE PRECISION DEFAULT NULL, gift_wrap_tax_currency DOUBLE PRECISION DEFAULT NULL, item_promotion_discount_currency DOUBLE PRECISION DEFAULT NULL, ship_promotion_discount_currency DOUBLE PRECISION DEFAULT NULL, vat_exclusive_item_price_currency DOUBLE PRECISION DEFAULT NULL, vat_exclusive_shipping_price_currency DOUBLE PRECISION DEFAULT NULL, vat_exclusive_giftwrap_price_currency DOUBLE PRECISION DEFAULT NULL, integrated TINYINT(1) NOT NULL, integration_number VARCHAR(255) DEFAULT NULL, INDEX IDX_145B403A4584665A (product_id), INDEX IDX_145B403A44F5D008 (brand_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE brand (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, brand_id INT DEFAULT NULL, sku VARCHAR(255) NOT NULL, asin VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, description VARCHAR(255) DEFAULT NULL, comments LONGTEXT DEFAULT NULL, active TINYINT(1) DEFAULT NULL, INDEX IDX_D34A04AD44F5D008 (brand_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE amazon_order ADD CONSTRAINT FK_145B403A4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE amazon_order ADD CONSTRAINT FK_145B403A44F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD44F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amazon_order DROP FOREIGN KEY FK_145B403A44F5D008');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD44F5D008');
        $this->addSql('ALTER TABLE amazon_order DROP FOREIGN KEY FK_145B403A4584665A');
        $this->addSql('DROP TABLE amazon_order');
        $this->addSql('DROP TABLE brand');
        $this->addSql('DROP TABLE product');
    }
}
