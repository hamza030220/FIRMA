<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add blameable relations and mandatory timestamps to maladie case entities.';
    }

    public function up(Schema $schema): void
    {
        $fallbackUserId = $this->getFallbackUserId();

        $this->ensureBlameableColumns('maladie_case');
        $this->connection->executeStatement(
            'UPDATE maladie_case SET created_by = COALESCE(created_by, utilisateur_id, :fallback), updated_by = COALESCE(updated_by, created_by, utilisateur_id, :fallback) WHERE created_by IS NULL OR updated_by IS NULL',
            ['fallback' => $fallbackUserId]
        );
        $this->addBlameableIndexesAndConstraints('maladie_case');
        $this->connection->executeStatement('ALTER TABLE maladie_case MODIFY created_by INT NOT NULL');

        $this->ensureBlameableColumns('maladie_case_update');
        $this->ensureUpdatedAtColumn('maladie_case_update');
        $this->connection->executeStatement(
            'UPDATE maladie_case_update u
             LEFT JOIN maladie_case c ON c.id = u.case_id
             SET u.created_by = COALESCE(u.created_by, c.created_by, c.utilisateur_id, :fallback),
                 u.updated_by = COALESCE(u.updated_by, c.created_by, c.utilisateur_id, :fallback),
                 u.updated_at = COALESCE(u.updated_at, u.created_at)
             WHERE u.created_by IS NULL OR u.updated_by IS NULL OR u.updated_at IS NULL',
            ['fallback' => $fallbackUserId]
        );
        $this->addBlameableIndexesAndConstraints('maladie_case_update');
        $this->connection->executeStatement('ALTER TABLE maladie_case_update MODIFY created_by INT NOT NULL');

        $this->ensureBlameableColumns('maladie_case_photo');
        $this->ensureUpdatedAtColumn('maladie_case_photo');
        $this->connection->executeStatement(
            'UPDATE maladie_case_photo p
             LEFT JOIN maladie_case_update u ON u.id = p.case_update_id
             LEFT JOIN maladie_case c ON c.id = u.case_id
             SET p.created_by = COALESCE(p.created_by, c.created_by, c.utilisateur_id, :fallback),
                 p.updated_by = COALESCE(p.updated_by, c.created_by, c.utilisateur_id, :fallback),
                 p.updated_at = COALESCE(p.updated_at, p.created_at)
             WHERE p.created_by IS NULL OR p.updated_by IS NULL OR p.updated_at IS NULL',
            ['fallback' => $fallbackUserId]
        );
        $this->addBlameableIndexesAndConstraints('maladie_case_photo');
        $this->connection->executeStatement('ALTER TABLE maladie_case_photo MODIFY created_by INT NOT NULL');

        $this->ensureBlameableColumns('solution_traitement_vote');
        $this->ensureUpdatedAtColumn('solution_traitement_vote');
        $this->connection->executeStatement(
            'UPDATE solution_traitement_vote
             SET created_by = COALESCE(created_by, utilisateur_id, :fallback),
                 updated_by = COALESCE(updated_by, created_by, utilisateur_id, :fallback),
                 updated_at = COALESCE(updated_at, created_at)
             WHERE created_by IS NULL OR updated_by IS NULL OR updated_at IS NULL',
            ['fallback' => $fallbackUserId]
        );
        $this->addBlameableIndexesAndConstraints('solution_traitement_vote');
        $this->connection->executeStatement('ALTER TABLE solution_traitement_vote MODIFY created_by INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->dropBlameable('solution_traitement_vote');
        $this->dropBlameable('maladie_case_photo');
        $this->dropBlameable('maladie_case_update');
        $this->dropBlameable('maladie_case');
    }

    private function ensureBlameableColumns(string $table): void
    {
        if (!$this->columnExists($table, 'created_by')) {
            $this->connection->executeStatement("ALTER TABLE $table ADD created_by INT DEFAULT NULL");
        }

        if (!$this->columnExists($table, 'updated_by')) {
            $this->connection->executeStatement("ALTER TABLE $table ADD updated_by INT DEFAULT NULL");
        }
    }

    private function ensureUpdatedAtColumn(string $table): void
    {
        if (!$this->columnExists($table, 'updated_at')) {
            $this->connection->executeStatement("ALTER TABLE $table ADD updated_at DATETIME DEFAULT NULL");
        }
    }

    private function addBlameableIndexesAndConstraints(string $table): void
    {
        $createdIndex = 'IDX_' . strtoupper($table) . '_CREATED_BY';
        $updatedIndex = 'IDX_' . strtoupper($table) . '_UPDATED_BY';
        $createdFk = 'FK_' . strtoupper($table) . '_CREATED_BY';
        $updatedFk = 'FK_' . strtoupper($table) . '_UPDATED_BY';

        if (!$this->indexExists($table, $createdIndex)) {
            $this->connection->executeStatement("CREATE INDEX $createdIndex ON $table (created_by)");
        }

        if (!$this->indexExists($table, $updatedIndex)) {
            $this->connection->executeStatement("CREATE INDEX $updatedIndex ON $table (updated_by)");
        }

        if (!$this->foreignKeyExists($table, $createdFk)) {
            $this->connection->executeStatement("ALTER TABLE $table ADD CONSTRAINT $createdFk FOREIGN KEY (created_by) REFERENCES utilisateurs (id) ON DELETE CASCADE");
        }

        if (!$this->foreignKeyExists($table, $updatedFk)) {
            $this->connection->executeStatement("ALTER TABLE $table ADD CONSTRAINT $updatedFk FOREIGN KEY (updated_by) REFERENCES utilisateurs (id) ON DELETE SET NULL");
        }
    }

    private function dropBlameable(string $table): void
    {
        $createdIndex = 'IDX_' . strtoupper($table) . '_CREATED_BY';
        $updatedIndex = 'IDX_' . strtoupper($table) . '_UPDATED_BY';
        $createdFk = 'FK_' . strtoupper($table) . '_CREATED_BY';
        $updatedFk = 'FK_' . strtoupper($table) . '_UPDATED_BY';

        if ($this->foreignKeyExists($table, $updatedFk)) {
            $this->connection->executeStatement("ALTER TABLE $table DROP FOREIGN KEY $updatedFk");
        }

        if ($this->foreignKeyExists($table, $createdFk)) {
            $this->connection->executeStatement("ALTER TABLE $table DROP FOREIGN KEY $createdFk");
        }

        if ($this->indexExists($table, $updatedIndex)) {
            $this->connection->executeStatement("DROP INDEX $updatedIndex ON $table");
        }

        if ($this->indexExists($table, $createdIndex)) {
            $this->connection->executeStatement("DROP INDEX $createdIndex ON $table");
        }

        if ($this->columnExists($table, 'updated_by')) {
            $this->connection->executeStatement("ALTER TABLE $table DROP COLUMN updated_by");
        }

        if ($this->columnExists($table, 'created_by')) {
            $this->connection->executeStatement("ALTER TABLE $table DROP COLUMN created_by");
        }

        if ($this->columnExists($table, 'updated_at') && in_array($table, ['maladie_case_update', 'maladie_case_photo', 'solution_traitement_vote'], true)) {
            $this->connection->executeStatement("ALTER TABLE $table DROP COLUMN updated_at");
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );
    }

    private function indexExists(string $table, string $index): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $index]
        );
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = \'FOREIGN KEY\'',
            [$table, $constraint]
        );
    }

    private function getFallbackUserId(): int
    {
        $userId = $this->connection->fetchOne('SELECT id FROM utilisateurs ORDER BY id ASC LIMIT 1');

        return $userId !== false ? (int) $userId : 1;
    }
}
