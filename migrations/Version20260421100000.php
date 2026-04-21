<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Keep forum moderation alerts as historical records when comments are deleted.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE forum_moderation_alert DROP FOREIGN KEY FK_9B2C69CF1E8FDE0');
        $this->addSql('ALTER TABLE forum_moderation_alert CHANGE commentaire_id commentaire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE forum_moderation_alert ADD CONSTRAINT FK_9B2C69CF1E8FDE0 FOREIGN KEY (commentaire_id) REFERENCES commentaire (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE forum_moderation_alert DROP FOREIGN KEY FK_9B2C69CF1E8FDE0');
        $this->addSql('ALTER TABLE forum_moderation_alert CHANGE commentaire_id commentaire_id INT NOT NULL');
        $this->addSql('ALTER TABLE forum_moderation_alert ADD CONSTRAINT FK_9B2C69CF1E8FDE0 FOREIGN KEY (commentaire_id) REFERENCES commentaire (id) ON DELETE CASCADE');
    }
}
