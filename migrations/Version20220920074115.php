<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220920074115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_sale_channel ADD recommended_price DOUBLE PRECISION DEFAULT NULL, ADD estimated_commission DOUBLE PRECISION DEFAULT NULL, ADD estimated_shipping DOUBLE PRECISION DEFAULT NULL, ADD estimated_commission_percent DOUBLE PRECISION DEFAULT NULL, ADD estimated_shipping_percent DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_sale_channel DROP recommended_price, DROP estimated_commission, DROP estimated_shipping, DROP estimated_commission_percent, DROP estimated_shipping_percent');
    }
}
