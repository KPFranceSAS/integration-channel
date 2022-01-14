<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220112111823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amazon_order ADD merchant_order_id VARCHAR(255) NOT NULL, ADD is_multiline TINYINT(1) DEFAULT NULL, ADD is_return TINYINT(1) DEFAULT NULL, CHANGE integrated integrated TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amazon_order DROP merchant_order_id, DROP is_multiline, DROP is_return, CHANGE integrated integrated TINYINT(1) NOT NULL');
    }
}
