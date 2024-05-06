<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240418142525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_type_categorizacion (id INT AUTO_INCREMENT NOT NULL, pim_product_type VARCHAR(255) NOT NULL, pim_product_label VARCHAR(255) DEFAULT NULL, decathlon_category VARCHAR(255) DEFAULT NULL, leroymerlin_category VARCHAR(255) DEFAULT NULL, boulanger_category VARCHAR(255) DEFAULT NULL, fnac_darty_category VARCHAR(255) DEFAULT NULL, mediamarkt_category VARCHAR(255) DEFAULT NULL, amazon_category VARCHAR(255) DEFAULT NULL, cdiscount_category VARCHAR(255) DEFAULT NULL, manomano_category VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE product_type_categorizacion');
    }
}
