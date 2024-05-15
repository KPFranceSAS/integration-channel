<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240514155258 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_type_categorizacion ADD amazon_fr_category VARCHAR(255) DEFAULT NULL, ADD amazon_de_category VARCHAR(255) DEFAULT NULL, ADD amazon_es_category VARCHAR(255) DEFAULT NULL, ADD amazon_it_category VARCHAR(255) DEFAULT NULL, ADD amazon_uk_category VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_type_categorizacion DROP amazon_fr_category, DROP amazon_de_category, DROP amazon_es_category, DROP amazon_it_category, DROP amazon_uk_category');
    }
}
