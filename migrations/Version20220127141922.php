<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220127141922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amazon_order ADD item_price_currency DOUBLE PRECISION DEFAULT NULL, ADD item_tax_currency DOUBLE PRECISION DEFAULT NULL, ADD shipping_price_currency DOUBLE PRECISION DEFAULT NULL, ADD shipping_tax_currency DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amazon_order DROP item_price_currency, DROP item_tax_currency, DROP shipping_price_currency, DROP shipping_tax_currency');
    }
}
