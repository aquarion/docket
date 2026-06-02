<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

class MigrateFromSqliteTest extends TestCase
{
    use RefreshDatabase;

    private string $sqlitePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sqlitePath = tempnam(sys_get_temp_dir(), 'docket_sqlite_').'.sqlite';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->sqlitePath)) {
            unlink($this->sqlitePath);
        }
        parent::tearDown();
    }

    private function sourceDb(): PDO
    {
        $pdo = new PDO("sqlite:{$this->sqlitePath}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    public function test_migrates_users_from_sqlite(): void
    {
        $source = $this->sourceDb();
        $source->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            email_verified_at TEXT,
            google_id TEXT NOT NULL,
            avatar TEXT,
            remember_token TEXT,
            created_at TEXT,
            updated_at TEXT
        )');
        $source->exec("INSERT INTO users VALUES (1, 'Alice', 'alice@example.com', NULL, 'g_alice', NULL, NULL, '2026-01-01 00:00:00', '2026-01-01 00:00:00')");

        $this->artisan('docket:migrate-from-sqlite', ['--sqlite-path' => $this->sqlitePath])
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'alice@example.com', 'name' => 'Alice']);
    }

    public function test_skips_table_when_destination_has_data(): void
    {
        DB::table('users')->insert([
            'name' => 'Bob', 'email' => 'bob@example.com',
            'google_id' => 'g_bob', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $source = $this->sourceDb();
        $source->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY, name TEXT, email TEXT, email_verified_at TEXT,
            google_id TEXT, avatar TEXT, remember_token TEXT, created_at TEXT, updated_at TEXT
        )');
        $source->exec("INSERT INTO users VALUES (1, 'Alice', 'alice@example.com', NULL, 'g_alice', NULL, NULL, '2026-01-01 00:00:00', '2026-01-01 00:00:00')");

        $this->artisan('docket:migrate-from-sqlite', ['--sqlite-path' => $this->sqlitePath])
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'bob@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'alice@example.com']);
    }

    public function test_force_flag_overwrites_existing_data(): void
    {
        DB::table('users')->insert([
            'name' => 'Bob', 'email' => 'bob@example.com',
            'google_id' => 'g_bob', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $source = $this->sourceDb();
        $source->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY, name TEXT, email TEXT, email_verified_at TEXT,
            google_id TEXT, avatar TEXT, remember_token TEXT, created_at TEXT, updated_at TEXT
        )');
        $source->exec("INSERT INTO users VALUES (1, 'Alice', 'alice@example.com', NULL, 'g_alice', NULL, NULL, '2026-01-01 00:00:00', '2026-01-01 00:00:00')");

        $this->artisan('docket:migrate-from-sqlite', [
            '--sqlite-path' => $this->sqlitePath,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'bob@example.com']);
    }

    public function test_skips_framework_tables(): void
    {
        $source = $this->sourceDb();
        $source->exec('CREATE TABLE migrations (id INTEGER PRIMARY KEY, migration TEXT, batch INTEGER)');
        $source->exec("INSERT INTO migrations VALUES (999, '2026_01_01_fake_migration', 1)");

        $countBefore = DB::table('migrations')->count();

        $this->artisan('docket:migrate-from-sqlite', ['--sqlite-path' => $this->sqlitePath])
            ->assertExitCode(0);

        // The migrations table in the destination should be unchanged
        $this->assertEquals($countBefore, DB::table('migrations')->count());
    }

    public function test_fails_when_sqlite_file_not_found(): void
    {
        $this->artisan('docket:migrate-from-sqlite', ['--sqlite-path' => '/nonexistent/path.sqlite'])
            ->assertExitCode(1);
    }

    public function test_skips_sqlite_sequence_table(): void
    {
        $source = $this->sourceDb();
        // SQLite creates sqlite_sequence when AUTOINCREMENT is used
        $source->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            email_verified_at TEXT,
            google_id TEXT NOT NULL,
            avatar TEXT,
            remember_token TEXT,
            created_at TEXT,
            updated_at TEXT
        )');
        $source->exec("INSERT INTO users VALUES (NULL, 'Alice', 'alice@example.com', NULL, 'g_alice', NULL, NULL, '2026-01-01 00:00:00', '2026-01-01 00:00:00')");

        // sqlite_sequence is now present — command should not crash
        $this->artisan('docket:migrate-from-sqlite', ['--sqlite-path' => $this->sqlitePath])
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
    }
}
