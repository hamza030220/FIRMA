<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add blameable user relations to forum moderation alerts and bookmarks.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE forum_moderation_alert DROP FOREIGN KEY FK_9B2C69CF1E8FDE0');
        $this->addSql('ALTER TABLE forum_moderation_alert DROP FOREIGN KEY FK_9B2C69CFB88E14F');
        $this->addSql('UPDATE forum_moderation_alert SET created_by = utilisateur_id WHERE created_by IS NULL');
        $this->addSql('ALTER TABLE forum_moderation_alert CHANGE created_by created_by INT NOT NULL');
        $this->addSql('ALTER TABLE forum_moderation_alert ADD CONSTRAINT FK_64949EBCDE12AB56 FOREIGN KEY (created_by) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_moderation_alert ADD CONSTRAINT FK_64949EBC16FE72E1 FOREIGN KEY (updated_by) REFERENCES utilisateurs (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_64949EBCDE12AB56 ON forum_moderation_alert (created_by)');
        $this->addSql('CREATE INDEX IDX_64949EBC16FE72E1 ON forum_moderation_alert (updated_by)');

        $this->addSql('ALTER TABLE forum_post_bookmark DROP FOREIGN KEY FK_4B0E7C324584665A');
        $this->addSql('ALTER TABLE forum_post_bookmark DROP FOREIGN KEY FK_4B0E7C32DB38439E');
        $this->addSql('ALTER TABLE forum_post_bookmark ADD updated_at DATETIME DEFAULT NULL, ADD created_by INT DEFAULT NULL, ADD updated_by INT DEFAULT NULL');
        $this->addSql('UPDATE forum_post_bookmark SET created_by = utilisateur_id WHERE created_by IS NULL');
        $this->addSql('ALTER TABLE forum_post_bookmark CHANGE created_by created_by INT NOT NULL');
        $this->addSql('ALTER TABLE forum_post_bookmark ADD CONSTRAINT FK_8D0F1179DE12AB56 FOREIGN KEY (created_by) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_post_bookmark ADD CONSTRAINT FK_8D0F117916FE72E1 FOREIGN KEY (updated_by) REFERENCES utilisateurs (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8D0F1179DE12AB56 ON forum_post_bookmark (created_by)');
        $this->addSql('CREATE INDEX IDX_8D0F117916FE72E1 ON forum_post_bookmark (updated_by)');

        $this->addSql('DROP INDEX idx_4b0e7c32db38439e ON forum_post_bookmark');
        $this->addSql('CREATE INDEX idx_forum_post_bookmark_user ON forum_post_bookmark (utilisateur_id)');
        $this->addSql('DROP INDEX idx_4b0e7c324584665a ON forum_post_bookmark');
        $this->addSql('CREATE INDEX idx_forum_post_bookmark_post ON forum_post_bookmark (post_id)');
        $this->addSql('DROP INDEX idx_4b0e7c32c5f6b89b ON forum_post_bookmark');
        $this->addSql('CREATE INDEX idx_forum_post_bookmark_type ON forum_post_bookmark (bookmark_type)');

        $this->addSql('ALTER TABLE forum_post_bookmark ADD CONSTRAINT `FK_4B0E7C324584665A` FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_post_bookmark ADD CONSTRAINT `FK_4B0E7C32DB38439E` FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE forum_post_bookmark DROP FOREIGN KEY FK_4B0E7C324584665A');
        $this->addSql('ALTER TABLE forum_post_bookmark DROP FOREIGN KEY FK_4B0E7C32DB38439E');
        $this->addSql('ALTER TABLE forum_post_bookmark DROP FOREIGN KEY FK_8D0F1179DE12AB56');
        $this->addSql('ALTER TABLE forum_post_bookmark DROP FOREIGN KEY FK_8D0F117916FE72E1');
        $this->addSql('DROP INDEX IDX_8D0F1179DE12AB56 ON forum_post_bookmark');
        $this->addSql('DROP INDEX IDX_8D0F117916FE72E1 ON forum_post_bookmark');
        $this->addSql('ALTER TABLE forum_post_bookmark DROP COLUMN updated_at');
        $this->addSql('DROP INDEX idx_forum_post_bookmark_user ON forum_post_bookmark');
        $this->addSql('DROP INDEX idx_forum_post_bookmark_post ON forum_post_bookmark');
        $this->addSql('DROP INDEX idx_forum_post_bookmark_type ON forum_post_bookmark');
        $this->addSql('ALTER TABLE forum_post_bookmark DROP COLUMN created_by, DROP COLUMN updated_by');
        $this->addSql('ALTER TABLE forum_post_bookmark ADD CONSTRAINT FK_4B0E7C324584665A FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_post_bookmark ADD CONSTRAINT FK_4B0E7C32DB38439E FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE forum_moderation_alert DROP FOREIGN KEY FK_64949EBCDE12AB56');
        $this->addSql('ALTER TABLE forum_moderation_alert DROP FOREIGN KEY FK_64949EBC16FE72E1');
        $this->addSql('DROP INDEX IDX_64949EBCDE12AB56 ON forum_moderation_alert');
        $this->addSql('DROP INDEX IDX_64949EBC16FE72E1 ON forum_moderation_alert');
        $this->addSql('ALTER TABLE forum_moderation_alert CHANGE created_by created_by INT DEFAULT NULL');
        $this->addSql('ALTER TABLE forum_moderation_alert ADD CONSTRAINT FK_9B2C69CF1E8FDE0 FOREIGN KEY (commentaire_id) REFERENCES commentaire (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE forum_moderation_alert ADD CONSTRAINT FK_9B2C69CFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE');
    }
}
