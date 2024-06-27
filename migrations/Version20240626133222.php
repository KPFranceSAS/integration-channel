<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240626133222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE brand_sale_channel (brand_id INT NOT NULL, sale_channel_id INT NOT NULL, INDEX IDX_1A511B6644F5D008 (brand_id), INDEX IDX_1A511B668FA145EE (sale_channel_id), PRIMARY KEY(brand_id, sale_channel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE brand_sale_channel ADD CONSTRAINT FK_1A511B6644F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE brand_sale_channel ADD CONSTRAINT FK_1A511B668FA145EE FOREIGN KEY (sale_channel_id) REFERENCES sale_channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product ADD ecotax DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE brand_sale_channel DROP FOREIGN KEY FK_1A511B6644F5D008');
        $this->addSql('ALTER TABLE brand_sale_channel DROP FOREIGN KEY FK_1A511B668FA145EE');
        $this->addSql('DROP TABLE brand_sale_channel');
        $this->addSql('ALTER TABLE product DROP ecotax');
    }
}
