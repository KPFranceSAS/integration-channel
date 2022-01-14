<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220111110717 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amazon_order DROP FOREIGN KEY FK_145B403A44F5D008');
        $this->addSql('DROP INDEX IDX_145B403A44F5D008 ON amazon_order');
        $this->addSql('ALTER TABLE amazon_order DROP brand_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amazon_order ADD brand_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE amazon_order ADD CONSTRAINT FK_145B403A44F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_145B403A44F5D008 ON amazon_order (brand_id)');
    }
}
