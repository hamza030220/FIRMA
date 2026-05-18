<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add blameable relations to maladie and solution_traitement tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE maladie CHANGE created_by created_by INT NOT NULL');
        $this->addSql('ALTER TABLE maladie ADD updated_by INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_MALADIE_CREATED_BY ON maladie (created_by)');
        $this->addSql('CREATE INDEX IDX_MALADIE_UPDATED_BY ON maladie (updated_by)');
        $this->addSql('ALTER TABLE maladie ADD CONSTRAINT FK_MALADIE_CREATED_BY FOREIGN KEY (created_by) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE maladie ADD CONSTRAINT FK_MALADIE_UPDATED_BY FOREIGN KEY (updated_by) REFERENCES utilisateurs (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE solution_traitement CHANGE created_by created_by INT NOT NULL');
        $this->addSql('ALTER TABLE solution_traitement ADD updated_by INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_SOLUTION_TRAITEMENT_CREATED_BY ON solution_traitement (created_by)');
        $this->addSql('CREATE INDEX IDX_SOLUTION_TRAITEMENT_UPDATED_BY ON solution_traitement (updated_by)');
        $this->addSql('ALTER TABLE solution_traitement ADD CONSTRAINT FK_SOLUTION_TRAITEMENT_CREATED_BY FOREIGN KEY (created_by) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE solution_traitement ADD CONSTRAINT FK_SOLUTION_TRAITEMENT_UPDATED_BY FOREIGN KEY (updated_by) REFERENCES utilisateurs (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE solution_traitement DROP FOREIGN KEY FK_SOLUTION_TRAITEMENT_CREATED_BY');
        $this->addSql('ALTER TABLE solution_traitement DROP FOREIGN KEY FK_SOLUTION_TRAITEMENT_UPDATED_BY');
        $this->addSql('DROP INDEX IDX_SOLUTION_TRAITEMENT_CREATED_BY ON solution_traitement');
        $this->addSql('DROP INDEX IDX_SOLUTION_TRAITEMENT_UPDATED_BY ON solution_traitement');
        $this->addSql('ALTER TABLE solution_traitement DROP COLUMN updated_by');
        $this->addSql('ALTER TABLE solution_traitement CHANGE created_by created_by INT DEFAULT NULL');

        $this->addSql('ALTER TABLE maladie DROP FOREIGN KEY FK_MALADIE_CREATED_BY');
        $this->addSql('ALTER TABLE maladie DROP FOREIGN KEY FK_MALADIE_UPDATED_BY');
        $this->addSql('DROP INDEX IDX_MALADIE_CREATED_BY ON maladie');
        $this->addSql('DROP INDEX IDX_MALADIE_UPDATED_BY ON maladie');
        $this->addSql('ALTER TABLE maladie DROP COLUMN updated_by');
        $this->addSql('ALTER TABLE maladie CHANGE created_by created_by INT DEFAULT NULL');
    }
}
