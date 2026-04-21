<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute une image optionnelle aux commentaires du forum.';
    }

    public function up(Schema $schema): void
    {
        $columns = array_keys($this->connection->createSchemaManager()->listTableColumns('commentaire'));

        if (!in_array('image_path', $columns, true)) {
            $this->addSql('ALTER TABLE commentaire ADD image_path VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $columns = array_keys($this->connection->createSchemaManager()->listTableColumns('commentaire'));

        if (in_array('image_path', $columns, true)) {
            $this->addSql('ALTER TABLE commentaire DROP image_path');
        }
    }
}
