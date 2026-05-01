<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-encoding',
    description: 'Repair UTF-8 mojibake (e.g. "Ã©" → "é") in all text columns of the database.',
)]
final class FixDbEncodingCommand extends Command
{
    public function __construct(private readonly Connection $conn)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be changed without writing')
            ->addOption('table', null, InputOption::VALUE_REQUIRED, 'Restrict to one table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dry = (bool) $input->getOption('dry-run');
        $onlyTable = $input->getOption('table');

        // 1) List all string/text columns of the current schema
        $sql = <<<SQL
            SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
            FROM information_schema.columns
            WHERE TABLE_SCHEMA = DATABASE()
              AND DATA_TYPE IN ('char','varchar','text','tinytext','mediumtext','longtext','json')
            ORDER BY TABLE_NAME, ORDINAL_POSITION
        SQL;
        $cols = $this->conn->fetchAllAssociative($sql);

        // Group columns by table and find PK column for each table
        $byTable = [];
        foreach ($cols as $c) {
            if ($onlyTable && $c['TABLE_NAME'] !== $onlyTable) {
                continue;
            }
            $byTable[$c['TABLE_NAME']][] = $c['COLUMN_NAME'];
        }

        $totalRows = 0;
        $totalCells = 0;
        $tablesTouched = 0;

        foreach ($byTable as $table => $columns) {
            // Find primary key
            $pkRow = $this->conn->fetchAssociative(
                "SELECT COLUMN_NAME FROM information_schema.key_column_usage
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME='PRIMARY'
                 ORDER BY ORDINAL_POSITION LIMIT 1",
                [$table]
            );
            if (!$pkRow) {
                $io->note("Skipping `$table` (no primary key)");
                continue;
            }
            $pk = $pkRow['COLUMN_NAME'];

            // WHERE: any text col contains a known mojibake marker.
            // Covers CP1252-mojibake (Ã, â€, ðŸ) and CP850-mojibake (├, ┬, Ô, Ö, ┴).
            $likeParts = [];
            foreach ($columns as $col) {
                $likeParts[] = "`$col` LIKE '%Ã%' OR `$col` LIKE '%â€%' OR `$col` LIKE '%ðŸ%'
                    OR `$col` LIKE '%├%' OR `$col` LIKE '%Ô%' OR `$col` LIKE '%Ö%'";
            }
            $where = implode(' OR ', $likeParts);

            $cols = '`' . $pk . '`,' . implode(',', array_map(fn($c) => "`$c`", $columns));
            $sel = "SELECT $cols FROM `$table` WHERE $where";

            try {
                $rows = $this->conn->fetchAllAssociative($sel);
            } catch (\Throwable $e) {
                $io->warning("Skip `$table`: " . $e->getMessage());
                continue;
            }

            if (!$rows) {
                continue;
            }

            $rowsTouched = 0;
            $cellsTouched = 0;
            foreach ($rows as $row) {
                $set = [];
                $params = [];
                foreach ($columns as $col) {
                    $v = $row[$col];
                    if ($v === null || $v === '') {
                        continue;
                    }
                    $fixed = $this->repair($v);
                    if ($fixed !== $v) {
                        $set[] = "`$col` = ?";
                        $params[] = $fixed;
                        $cellsTouched++;
                    }
                }
                if (!$set) {
                    continue;
                }
                $params[] = $row[$pk];
                $rowsTouched++;
                if (!$dry) {
                    $this->conn->executeStatement(
                        "UPDATE `$table` SET " . implode(', ', $set) . " WHERE `$pk` = ?",
                        $params
                    );
                }
            }

            if ($rowsTouched > 0) {
                $tablesTouched++;
                $totalRows += $rowsTouched;
                $totalCells += $cellsTouched;
                $io->writeln(sprintf(
                    " <info>%s</info>: %d row(s), %d cell(s)%s",
                    $table, $rowsTouched, $cellsTouched, $dry ? ' [DRY-RUN]' : ''
                ));
            }
        }

        $io->success(sprintf(
            '%s%d table(s), %d row(s), %d cell(s) repaired.',
            $dry ? '[DRY-RUN] ' : '',
            $tablesTouched, $totalRows, $totalCells
        ));

        return Command::SUCCESS;
    }

    /**
     * Try to undo UTF-8-mis-decoded-as-CP1252-or-CP850 mojibake.
     * Returns the repaired string, or the original if no safe repair is possible.
     */
    private function repair(string $s): string
    {
        // Try several legacy encodings; the first one whose round-trip yields
        // valid UTF-8 with FEWER mojibake markers wins.
        $candidates = ['CP1252', 'CP850', 'ISO-8859-1'];
        $bestScore = $this->mojibakeScore($s);
        $best = $s;
        foreach ($candidates as $enc) {
            $bytes = @mb_convert_encoding($s, $enc, 'UTF-8');
            if ($bytes === '' || $bytes === $s) {
                continue;
            }
            if (!mb_check_encoding($bytes, 'UTF-8')) {
                continue;
            }
            $score = $this->mojibakeScore($bytes);
            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $bytes;
            }
        }
        return $best;
    }

    private function mojibakeScore(string $s): int
    {
        return preg_match_all(
            '/Ã[\x{0080}-\x{00BF}]|â€[\x{0090}-\x{00BF}]|ðŸ|âœ|â³|├|Ô|Ö/u',
            $s
        ) ?: 0;
    }
}
