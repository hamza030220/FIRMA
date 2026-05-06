<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add timestamp audit columns to forum post and reaction tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT NULL, ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE post SET created_at = date_creation WHERE created_at IS NULL');
        $this->addSql('ALTER TABLE post MODIFY created_at DATETIME NOT NULL');

        $this->addSql('ALTER TABLE post_reaction ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT NULL, ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE post_reaction SET created_at = date_creation WHERE created_at IS NULL');
        $this->addSql('ALTER TABLE post_reaction MODIFY created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post DROP COLUMN created_at, DROP COLUMN updated_at');
        $this->addSql('ALTER TABLE post_reaction DROP COLUMN created_at, DROP COLUMN updated_at');
    }
}
