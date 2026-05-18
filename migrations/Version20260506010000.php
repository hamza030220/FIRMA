<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow deleting a maladie without deleting shared solution_traitement records.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE solution_traitement DROP FOREIGN KEY fk_solution_maladie');
        $this->addSql('ALTER TABLE solution_traitement ADD CONSTRAINT fk_solution_maladie FOREIGN KEY (maladie_id) REFERENCES maladie (id_maladie) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE solution_traitement DROP FOREIGN KEY fk_solution_maladie');
        $this->addSql('ALTER TABLE solution_traitement ADD CONSTRAINT fk_solution_maladie FOREIGN KEY (maladie_id) REFERENCES maladie (id_maladie) ON DELETE CASCADE');
    }
}
