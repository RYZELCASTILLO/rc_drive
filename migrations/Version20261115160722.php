<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261115160722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE inquiries (id INT AUTO_INCREMENT NOT NULL, customer_name VARCHAR(100) NOT NULL, subject VARCHAR(150) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE inquiry_messages (id INT AUTO_INCREMENT NOT NULL, sender VARCHAR(10) NOT NULL, body LONGTEXT NOT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, inquiry_id INT NOT NULL, INDEX IDX_7D55549FA7AD6D71 (inquiry_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE inquiry_messages ADD CONSTRAINT FK_7D55549FA7AD6D71 FOREIGN KEY (inquiry_id) REFERENCES inquiries (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE inquiry_messages DROP FOREIGN KEY FK_7D55549FA7AD6D71');
        $this->addSql('DROP TABLE inquiries');
        $this->addSql('DROP TABLE inquiry_messages');
    }
}
