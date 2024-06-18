<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240611162227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amazon_return ADD status_integration INT DEFAULT NULL, ADD sale_return_document VARCHAR(255) DEFAULT NULL, ADD logs JSON DEFAULT NULL');
        $this->addSql("UPDATE amazon_return SET status_integration = 0, logs = '[]'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amazon_return DROP status_integration, DROP sale_return_document, DROP logs');
    }
}
