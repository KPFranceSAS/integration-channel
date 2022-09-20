<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220919102741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_sale_channel (user_id INT NOT NULL, sale_channel_id INT NOT NULL, INDEX IDX_572B30D7A76ED395 (user_id), INDEX IDX_572B30D78FA145EE (sale_channel_id), PRIMARY KEY(user_id, sale_channel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_sale_channel ADD CONSTRAINT FK_572B30D7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_sale_channel ADD CONSTRAINT FK_572B30D78FA145EE FOREIGN KEY (sale_channel_id) REFERENCES sale_channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD is_admin TINYINT(1) DEFAULT NULL, ADD is_pricing_manager TINYINT(1) DEFAULT NULL, ADD is_fba_manager TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_sale_channel DROP FOREIGN KEY FK_572B30D7A76ED395');
        $this->addSql('ALTER TABLE user_sale_channel DROP FOREIGN KEY FK_572B30D78FA145EE');
        $this->addSql('DROP TABLE user_sale_channel');
        $this->addSql('ALTER TABLE user DROP is_admin, DROP is_pricing_manager, DROP is_fba_manager');
    }
}
