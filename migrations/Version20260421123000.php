<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create internal user notifications for forum warnings.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_notification (id INT AUTO_INCREMENT NOT NULL, recipient_id INT NOT NULL, type VARCHAR(50) NOT NULL, title VARCHAR(150) NOT NULL, message LONGTEXT NOT NULL, link_url VARCHAR(255) DEFAULT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, INDEX IDX_D9F9A6ECE92F8F78 (recipient_id), INDEX IDX_D9F9A6ECB03A8386 (is_read), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_notification ADD CONSTRAINT FK_D9F9A6ECE92F8F78 FOREIGN KEY (recipient_id) REFERENCES utilisateurs (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_notification');
    }
}
