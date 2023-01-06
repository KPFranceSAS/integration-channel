<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230106163402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE amazon_removal (id INT AUTO_INCREMENT NOT NULL, order_id VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, order_type VARCHAR(255) NOT NULL, request_date DATETIME NOT NULL, ship_city VARCHAR(255) NOT NULL, ship_postal_code VARCHAR(255) NOT NULL, ship_state VARCHAR(255) NOT NULL, ship_country VARCHAR(255) DEFAULT NULL, notifyed_creation TINYINT(1) NOT NULL, notifyed_end TINYINT(1) NOT NULL, amazon_order_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', logs JSON DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE amazon_removal_order ADD amazon_removal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE amazon_removal_order ADD CONSTRAINT FK_E2A149578E1D30F4 FOREIGN KEY (amazon_removal_id) REFERENCES amazon_removal (id)');
        $this->addSql('CREATE INDEX IDX_E2A149578E1D30F4 ON amazon_removal_order (amazon_removal_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amazon_removal_order DROP FOREIGN KEY FK_E2A149578E1D30F4');
        $this->addSql('DROP TABLE amazon_removal');
        $this->addSql('DROP INDEX IDX_E2A149578E1D30F4 ON amazon_removal_order');
        $this->addSql('ALTER TABLE amazon_removal_order DROP amazon_removal_id');
    }
}
