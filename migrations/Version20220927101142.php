<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220927101142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_sale_channel_history ADD product_sale_channel_id INT NOT NULL');
        $this->addSql('ALTER TABLE product_sale_channel_history ADD CONSTRAINT FK_B068CDB3BD2A4073 FOREIGN KEY (product_sale_channel_id) REFERENCES product_sale_channel (id)');
        $this->addSql('CREATE INDEX IDX_B068CDB3BD2A4073 ON product_sale_channel_history (product_sale_channel_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_sale_channel_history DROP FOREIGN KEY FK_B068CDB3BD2A4073');
        $this->addSql('DROP INDEX IDX_B068CDB3BD2A4073 ON product_sale_channel_history');
        $this->addSql('ALTER TABLE product_sale_channel_history DROP product_sale_channel_id');
    }
}
