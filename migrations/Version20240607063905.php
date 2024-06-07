<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240607063905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_pricing DROP FOREIGN KEY FK_F6E105DFA76ED395');
        $this->addSql('ALTER TABLE import_pricing CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE import_pricing ADD CONSTRAINT FK_F6E105DFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_pricing DROP FOREIGN KEY FK_F6E105DFA76ED395');
        $this->addSql('ALTER TABLE import_pricing CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE import_pricing ADD CONSTRAINT FK_F6E105DFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
