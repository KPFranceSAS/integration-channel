<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240930102159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketplace_invoice ADD settlement_id INT DEFAULT NULL, ADD channel VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE marketplace_invoice ADD CONSTRAINT FK_7483C458C2B9C425 FOREIGN KEY (settlement_id) REFERENCES settlement (id)');
        $this->addSql('CREATE INDEX IDX_7483C458C2B9C425 ON marketplace_invoice (settlement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketplace_invoice DROP FOREIGN KEY FK_7483C458C2B9C425');
        $this->addSql('DROP INDEX IDX_7483C458C2B9C425 ON marketplace_invoice');
        $this->addSql('ALTER TABLE marketplace_invoice DROP settlement_id, DROP channel');
    }
}
