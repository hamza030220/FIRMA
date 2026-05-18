<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add forum post image and external sync reference fields.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post ADD image_url VARCHAR(255) DEFAULT NULL, ADD external_reference VARCHAR(150) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_post_external_reference ON post (external_reference)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_post_external_reference ON post');
        $this->addSql('ALTER TABLE post DROP image_url, DROP external_reference');
    }
}
