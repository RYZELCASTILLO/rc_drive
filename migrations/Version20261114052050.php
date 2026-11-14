<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261114052050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bookings (id INT AUTO_INCREMENT NOT NULL, customer_name VARCHAR(100) NOT NULL, phone VARCHAR(20) NOT NULL, pickup_date DATE NOT NULL, return_date DATE NOT NULL, total_fee NUMERIC(10, 2) NOT NULL, status VARCHAR(30) NOT NULL, payment_status VARCHAR(30) NOT NULL, payment_type VARCHAR(30) DEFAULT NULL, payment_reference VARCHAR(60) DEFAULT NULL, amount_paid NUMERIC(10, 2) NOT NULL, cancel_reason VARCHAR(255) DEFAULT NULL, late_fee NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, vehicle_id INT NOT NULL, INDEX IDX_7A853C35545317D1 (vehicle_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vehicles (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, brand VARCHAR(100) NOT NULL, price_per_day NUMERIC(10, 2) NOT NULL, total_units INT NOT NULL, availability VARCHAR(20) NOT NULL, image VARCHAR(100) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE bookings ADD CONSTRAINT FK_7A853C35545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bookings DROP FOREIGN KEY FK_7A853C35545317D1');
        $this->addSql('DROP TABLE bookings');
        $this->addSql('DROP TABLE vehicles');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
