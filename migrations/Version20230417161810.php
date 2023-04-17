<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230417161810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ADD uk3pl_business_central_stock INT DEFAULT NULL, ADD uk3pl_purchase_business_central_stock INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_stock_daily ADD uk3pl_business_central_stock INT DEFAULT NULL, ADD uk3pl_purchase_business_central_stock INT DEFAULT NULL');
        $this->addSql('UPDATE product SET uk3pl_business_central_stock =0, uk3pl_purchase_business_central_stock =0');
        $this->addSql('UPDATE product_stock_daily SET uk3pl_business_central_stock =0, uk3pl_purchase_business_central_stock  =0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP uk3pl_business_central_stock, DROP uk3pl_purchase_business_central_stock');
        $this->addSql('ALTER TABLE product_stock_daily DROP uk3pl_business_central_stock, DROP uk3pl_purchase_business_central_stock');
    }
}
