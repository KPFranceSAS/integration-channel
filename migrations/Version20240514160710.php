<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240514160710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_type_categorizacion ADD nb_product_amazon_es INT DEFAULT NULL, ADD nb_product_amazon_fr INT DEFAULT NULL, ADD nb_product_amazon_de INT DEFAULT NULL, ADD nb_product_amazon_uk INT DEFAULT NULL, ADD nb_product_amazon_it INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_type_categorizacion DROP nb_product_amazon_es, DROP nb_product_amazon_fr, DROP nb_product_amazon_de, DROP nb_product_amazon_uk, DROP nb_product_amazon_it');
    }
}
