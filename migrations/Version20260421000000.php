<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create forum moderation alert table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE forum_moderation_alert (id INT AUTO_INCREMENT NOT NULL, commentaire_id INT NOT NULL, utilisateur_id INT NOT NULL, original_content LONGTEXT NOT NULL, masked_content LONGTEXT NOT NULL, matched_words JSON NOT NULL, status VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, reviewed_at DATETIME DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, INDEX IDX_9B2C69CF1E8FDE0 (commentaire_id), INDEX IDX_9B2C69CFFB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE forum_moderation_alert ADD CONSTRAINT FK_9B2C69CF1E8FDE0 FOREIGN KEY (commentaire_id) REFERENCES commentaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_moderation_alert ADD CONSTRAINT FK_9B2C69CFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE forum_moderation_alert');
    }
}
