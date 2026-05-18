<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421240000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add forum bookmarks and pinned posts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post ADD is_pinned TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('CREATE TABLE forum_post_bookmark (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, utilisateur_id INT NOT NULL, bookmark_type VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_4B0E7C324584665A (post_id), INDEX IDX_4B0E7C32DB38439E (utilisateur_id), INDEX IDX_4B0E7C32C5F6B89B (bookmark_type), UNIQUE INDEX uniq_forum_post_bookmark_user_post_type (post_id, utilisateur_id, bookmark_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE forum_post_bookmark ADD CONSTRAINT FK_4B0E7C324584665A FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_post_bookmark ADD CONSTRAINT FK_4B0E7C32DB38439E FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post DROP is_pinned');
        $this->addSql('DROP TABLE forum_post_bookmark');
    }
}
