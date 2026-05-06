<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit columns to forum_moderation_alert.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE forum_moderation_alert ADD updated_at DATETIME DEFAULT NULL, ADD created_by INT DEFAULT NULL, ADD updated_by INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE forum_moderation_alert DROP COLUMN updated_at, DROP COLUMN created_by, DROP COLUMN updated_by');
    }
}
