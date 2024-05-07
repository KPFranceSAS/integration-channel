<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240507133021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_type_categorizacion ADD nb_product_decathlon INT DEFAULT NULL, ADD nb_product_leroymerlin INT DEFAULT NULL, ADD nb_product_boulanger INT DEFAULT NULL, ADD nb_product_fnac_darty INT DEFAULT NULL, ADD nb_product_mediamarkt INT DEFAULT NULL, ADD nb_product_manomano INT DEFAULT NULL, ADD nb_product_amazon INT DEFAULT NULL, ADD nb_product_cdiscount INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_type_categorizacion DROP nb_product_decathlon, DROP nb_product_leroymerlin, DROP nb_product_boulanger, DROP nb_product_fnac_darty, DROP nb_product_mediamarkt, DROP nb_product_manomano, DROP nb_product_amazon, DROP nb_product_cdiscount');
    }
}
