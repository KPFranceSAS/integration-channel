<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221021073206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sale_channel ADD integration_channel_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sale_channel ADD CONSTRAINT FK_D7E74CD53D6A9E29 FOREIGN KEY (integration_channel_id) REFERENCES integration_channel (id)');
        $this->addSql('CREATE INDEX IDX_D7E74CD53D6A9E29 ON sale_channel (integration_channel_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sale_channel DROP FOREIGN KEY FK_D7E74CD53D6A9E29');
        $this->addSql('DROP INDEX IDX_D7E74CD53D6A9E29 ON sale_channel');
        $this->addSql('ALTER TABLE sale_channel DROP integration_channel_id');
    }
}
