<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240624145733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ADD msrp_eur DOUBLE PRECISION DEFAULT NULL, ADD msrp_gbp DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE product_sale_channel ADD override_price TINYINT(1) DEFAULT NULL');
        $this->addSql('UPDATE product_sale_channel SET override_price = 1 WHERE  price is not null');
        $this->addSql('UPDATE product_sale_channel SET override_price = 0 WHERE  price is null');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP msrp_eur, DROP msrp_gbp');
        $this->addSql('ALTER TABLE product_sale_channel DROP override_price');
    }
}
