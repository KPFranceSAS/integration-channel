<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241211103233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ADD gspr_name VARCHAR(255) DEFAULT NULL, ADD gspr_address VARCHAR(255) DEFAULT NULL, ADD gspr_email VARCHAR(255) DEFAULT NULL, ADD gspr_country VARCHAR(255) DEFAULT NULL, ADD gspr_city VARCHAR(255) DEFAULT NULL, ADD gspr_postal_code VARCHAR(255) DEFAULT NULL, ADD gspr_website VARCHAR(255) DEFAULT NULL, ADD gspr_phone VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP gspr_name, DROP gspr_address, DROP gspr_email, DROP gspr_country, DROP gspr_city, DROP gspr_postal_code, DROP gspr_website, DROP gspr_phone');
    }
}
