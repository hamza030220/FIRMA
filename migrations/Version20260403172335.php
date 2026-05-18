<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403172335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration neutralisee car elle supprimait des tables existantes de la base firma';
    }

    public function up(Schema $schema): void
    {
        // Intentionally left blank.
        // This migration was auto-generated against a mismatched schema and would remove existing tables.
    }

    public function down(Schema $schema): void
    {
        // Intentionally left blank.
    }
}
