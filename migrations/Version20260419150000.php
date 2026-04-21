<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add emoji reactions to forum posts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE post_reaction (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, utilisateur_id INT NOT NULL, type VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, UNIQUE INDEX uniq_post_reaction_user_post (post_id, utilisateur_id), INDEX idx_post_reaction_post (post_id), INDEX idx_post_reaction_user (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE post_reaction ADD CONSTRAINT FK_7E3C0E0D4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_reaction ADD CONSTRAINT FK_7E3C0E0DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post_reaction DROP FOREIGN KEY FK_7E3C0E0D4B89032C');
        $this->addSql('ALTER TABLE post_reaction DROP FOREIGN KEY FK_7E3C0E0DFB88E14F');
        $this->addSql('DROP TABLE post_reaction');
    }
}
