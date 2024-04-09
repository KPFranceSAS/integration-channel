<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240409071045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ADD enabled_fbm TINYINT(1) DEFAULT 0');
        $this->addSql('ALTER TABLE product_sale_channel DROP enabled_fbm');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP enabled_fbm');
        $this->addSql('ALTER TABLE product_sale_channel ADD enabled_fbm TINYINT(1) DEFAULT 0');
    }
}
