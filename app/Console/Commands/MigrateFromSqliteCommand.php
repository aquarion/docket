<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class MigrateFromSqliteCommand extends Command
{
    protected $signature = 'docket:migrate-from-sqlite
                            {--sqlite-path= : Path to the SQLite database file (default: database/database.sqlite)}
                            {--force : Overwrite tables that already contain data}';

    protected $description = 'Migrate data from a SQLite database into the current database (one-time operation)';

    private const SKIP_TABLES = [
        'migrations', 'cache', 'cache_locks', 'sessions',
        'jobs', 'failed_jobs', 'job_batches',
        'sqlite_sequence', 'password_reset_tokens', 'personal_access_tokens',
    ];

    public function handle(): int
    {
        $sqlitePath = $this->option('sqlite-path') ?? database_path('database.sqlite');

        if (! file_exists($sqlitePath)) {
            $this->error("SQLite database not found: {$sqlitePath}");

            return Command::FAILURE;
        }

        $source = new PDO("sqlite:{$sqlitePath}");
        $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tables = $source->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")
            ->fetchAll(PDO::FETCH_COLUMN);

        $tables = array_values(array_filter($tables, fn ($t) => ! in_array($t, self::SKIP_TABLES)));

        $migrated = 0;
        $skipped = 0;

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        foreach ($tables as $table) {
            $existingCount = DB::table($table)->count();

            if ($existingCount > 0 && ! $this->option('force')) {
                $this->warn("  Skipped {$table} ({$existingCount} rows already exist; use --force to overwrite)");
                $skipped++;

                continue;
            }

            if ($existingCount > 0) {
                $this->truncateTable($table);
            }

            $quotedTable = '"'.str_replace('"', '""', $table).'"';
            $rows = $source->query("SELECT * FROM {$quotedTable}")->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                $this->line("  {$table}: 0 rows (empty, skipped)");

                continue;
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table($table)->insert($chunk);
            }
            $count = count($rows);
            $this->info("  {$table}: {$count} row(s) migrated");
            $migrated++;
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->newLine();
        $this->info("Done. Tables migrated: {$migrated}, skipped: {$skipped}.");

        return Command::SUCCESS;
    }

    private function truncateTable(string $table): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // FK checks already disabled globally by handle() for the full migration
            DB::table($table)->truncate();
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
            DB::table($table)->truncate();
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::table($table)->truncate();
        }
    }
}
