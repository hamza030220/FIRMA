<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add blameable audit columns to forum post and reaction tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post ADD created_by INT DEFAULT NULL, ADD updated_by INT DEFAULT NULL');
        $this->addSql('UPDATE post SET created_by = utilisateur_id WHERE created_by IS NULL');
        $this->addSql('ALTER TABLE post MODIFY created_by INT NOT NULL');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_POST_CREATED_BY FOREIGN KEY (created_by) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_POST_UPDATED_BY FOREIGN KEY (updated_by) REFERENCES utilisateurs (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_POST_CREATED_BY ON post (created_by)');
        $this->addSql('CREATE INDEX IDX_POST_UPDATED_BY ON post (updated_by)');

        $this->addSql('ALTER TABLE post_reaction ADD created_by INT DEFAULT NULL, ADD updated_by INT DEFAULT NULL');
        $this->addSql('UPDATE post_reaction SET created_by = utilisateur_id WHERE created_by IS NULL');
        $this->addSql('ALTER TABLE post_reaction MODIFY created_by INT NOT NULL');
        $this->addSql('ALTER TABLE post_reaction ADD CONSTRAINT FK_POST_REACTION_CREATED_BY FOREIGN KEY (created_by) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_reaction ADD CONSTRAINT FK_POST_REACTION_UPDATED_BY FOREIGN KEY (updated_by) REFERENCES utilisateurs (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_POST_REACTION_CREATED_BY ON post_reaction (created_by)');
        $this->addSql('CREATE INDEX IDX_POST_REACTION_UPDATED_BY ON post_reaction (updated_by)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post_reaction DROP FOREIGN KEY FK_POST_REACTION_CREATED_BY');
        $this->addSql('ALTER TABLE post_reaction DROP FOREIGN KEY FK_POST_REACTION_UPDATED_BY');
        $this->addSql('DROP INDEX IDX_POST_REACTION_CREATED_BY ON post_reaction');
        $this->addSql('DROP INDEX IDX_POST_REACTION_UPDATED_BY ON post_reaction');
        $this->addSql('ALTER TABLE post_reaction DROP COLUMN created_by, DROP COLUMN updated_by');

        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_POST_CREATED_BY');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_POST_UPDATED_BY');
        $this->addSql('DROP INDEX IDX_POST_CREATED_BY ON post');
        $this->addSql('DROP INDEX IDX_POST_UPDATED_BY ON post');
        $this->addSql('ALTER TABLE post DROP COLUMN created_by, DROP COLUMN updated_by');
    }
}
