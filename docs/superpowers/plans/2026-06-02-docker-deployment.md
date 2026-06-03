# Docker Deployment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Docket deployable as a Docker container via the `firth_laravel_app` Ansible role, mirroring Sprouter's production model.

**Architecture:** A multi-stage FPM Dockerfile builds a production image pushed to GHCR. GitHub Actions CI runs tests, builds/pushes the image, and deploys via SSH. Ansible manages the server-side docker-compose and nginx vhost. The database switches from SQLite to MySQL, with a one-time migration command provided.

**Tech Stack:** PHP 8.4-FPM, Docker/docker-compose, GitHub Actions, GHCR (ghcr.io), Ansible (`firth_laravel_app` role), MySQL, Redis.

**Repos:** `aquarion/docket` (primary) and `aquarion/autopelago` (deployment config).

---

## File Map

### docket repo

| Action | Path | Purpose |
|---|---|---|
| Rewrite | `Dockerfile` | Multi-stage FPM production image |
| Create | `docker/entrypoint.sh` | Container startup: validate, cache, migrate, start fpm |
| Modify | `docker-compose.yaml` | Add MySQL service to Sail |
| Modify | `.env.example` | Switch DB to MySQL |
| Modify | `phpunit.xml` | Override DB to SQLite for CI tests |
| Create | `app/Console/Commands/MigrateFromSqliteCommand.php` | One-time data migration command |
| Create | `tests/Feature/MigrateFromSqliteTest.php` | Tests for migration command |
| Create | `.github/workflows/ci.yml` | Test + build/push + deploy |
| Create | `.github/workflows/release.yml` | Semver tag + calls ci.yml |
| Rewrite | `.github/workflows/dependabot-make-release.yml` | Sprouter-style weekly dep release |
| Delete | `.github/workflows/laravel.yml` | Replaced by ci.yml |
| Delete | `.github/workflows/auto-tag-release.yml` | Replaced by release.yml |
| Delete | `.github/workflows/automerge.yml` | Replaced by dependabot-make-release.yml |
| Delete | `.github/workflows/manage_artifacts.yml` | Removed |

### autopelago repo

| Action | Path | Purpose |
|---|---|---|
| Modify | `roles/firth_laravel_app/tasks/app.yml` | Add `additional_env` to `fla_ctx` |
| Modify | `roles/firth_laravel_app/tasks/staging.yml` | Propagate `additional_env` to staging context |
| Modify | `host_vars/firth.water.gkhs.net/laravel_apps.yml` | Add docket deployment config |

---

## Task 1: Dockerfile and entrypoint

**Files:**
- Rewrite: `Dockerfile`
- Create: `docker/entrypoint.sh`

- [ ] **Step 1: Rewrite Dockerfile**

Replace the entire contents of `Dockerfile`:

```dockerfile
FROM node:22-alpine AS node-deps
WORKDIR /var/www/html
COPY package.json package-lock.json ./
RUN npm ci

FROM php:8.4-fpm-alpine
WORKDIR /var/www/html

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/bin/install-php-extensions

RUN apk add --no-cache git unzip \
    && install-php-extensions pdo_mysql redis pcntl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY --from=node-deps /var/www/html/node_modules node_modules
COPY . .
RUN mkdir -p bootstrap/cache storage/framework/sessions storage/framework/views storage/framework/cache storage/logs storage/app/public \
    && cp .env.example .env \
    && php artisan key:generate --force \
    && php artisan package:discover --ansi \
    && npm run build \
    && rm .env \
    && rm -rf node_modules

RUN chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

USER www-data

EXPOSE 9000

ARG APP_VERSION=dev
ARG APP_PR_NUMBER=
ARG APP_BRANCH=

ENV APP_VERSION=$APP_VERSION
ENV APP_PR_NUMBER=$APP_PR_NUMBER
ENV APP_BRANCH=$APP_BRANCH

LABEL org.opencontainers.image.version=$APP_VERSION \
      org.opencontainers.image.revision=$APP_PR_NUMBER \
      org.opencontainers.image.ref.name=$APP_BRANCH

ENTRYPOINT ["/entrypoint.sh"]
```

- [ ] **Step 2: Create docker/entrypoint.sh**

Create `docker/entrypoint.sh`:

```sh
#!/bin/sh
set -e

if [ -z "${APP_KEY}" ]; then
    echo "[entrypoint] ERROR: APP_KEY is not set. Set it in your environment." >&2
    exit 1
fi

echo "[entrypoint] Creating storage directories..."
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public

echo "[entrypoint] Caching config..."
php artisan config:cache

echo "[entrypoint] Caching views..."
php artisan view:cache

# Set RUN_MIGRATIONS=false on additional replicas to avoid concurrent migration attempts.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "[entrypoint] Running migrations..."
    php artisan migrate --force
fi

echo "[entrypoint] Starting PHP-FPM..."
exec php-fpm
```

- [ ] **Step 3: Verify the build**

```bash
docker build -t docket:test .
```

Expected: build completes without error, image created.

- [ ] **Step 4: Commit**

```bash
git add Dockerfile docker/entrypoint.sh
git commit -m "🎇 Add production Dockerfile and entrypoint"
```

---

## Task 2: Sail compose and env

**Files:**
- Modify: `docker-compose.yaml`
- Modify: `.env.example`
- Modify: `phpunit.xml`

- [ ] **Step 1: Add MySQL to docker-compose.yaml**

In `docker-compose.yaml`, add the `mysql` service after the `redis` service:

```yaml
    mysql:
        image: 'mysql:8.0'
        environment:
            MYSQL_DATABASE: '${DB_DATABASE:-docket}'
            MYSQL_USER: '${DB_USERNAME:-docket}'
            MYSQL_PASSWORD: '${DB_PASSWORD:-secret}'
            MYSQL_ROOT_PASSWORD: '${DB_ROOT_PASSWORD:-secret}'
        volumes:
            - 'sail-mysql:/var/lib/mysql'
        networks:
            - sail
        healthcheck:
            test:
                - CMD
                - mysqladmin
                - ping
                - '-h'
                - localhost
                - '-u'
                - root
                - '-p${DB_ROOT_PASSWORD:-secret}'
            retries: 10
            timeout: 5s
```

Update `laravel.test` service's `depends_on` to add MySQL with a health condition:

```yaml
        depends_on:
            mysql:
                condition: service_healthy
            redis:
                condition: service_started
            selenium:
                condition: service_started
```

Add `sail-mysql` to the `volumes` block:

```yaml
volumes:
    sail-redis:
        driver: local
    sail-mysql:
        driver: local
```

- [ ] **Step 2: Update .env.example DB config**

In `.env.example`, replace:
```
DB_CONNECTION=sqlite
```
with:
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=docket
DB_USERNAME=docket
DB_PASSWORD=secret
```

- [ ] **Step 3: Add SQLite override to phpunit.xml**

In `phpunit.xml`, add two `<env>` entries inside the `<php>` block (after the existing ones):

```xml
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
```

This ensures the test suite uses an in-memory SQLite database regardless of `.env`.

- [ ] **Step 4: Verify tests still pass**

```bash
vendor/bin/phpunit
```

Expected: all tests pass.

- [ ] **Step 5: Commit**

```bash
git add docker-compose.yaml .env.example phpunit.xml
git commit -m "🔄️ Switch database to MySQL, keep Sail for local dev"
```

---

## Task 3: SQLite → MySQL migration command

**Files:**
- Create: `tests/Feature/MigrateFromSqliteTest.php`
- Create: `app/Console/Commands/MigrateFromSqliteCommand.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/MigrateFromSqliteTest.php`:

```php
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
        $this->sqlitePath = tempnam(sys_get_temp_dir(), 'docket_sqlite_') . '.sqlite';
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
}
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
vendor/bin/phpunit tests/Feature/MigrateFromSqliteTest.php
```

Expected: ERRORS — class `App\Console\Commands\MigrateFromSqliteCommand` not found.

- [ ] **Step 3: Implement the command**

Create `app/Console/Commands/MigrateFromSqliteCommand.php`:

```php
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

    private const SKIP_TABLES = ['migrations', 'cache', 'cache_locks', 'sessions', 'jobs', 'failed_jobs', 'job_batches'];

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

            $rows = $source->query("SELECT * FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                $this->line("  {$table}: 0 rows (empty, skipped)");

                continue;
            }

            DB::table($table)->insert($rows);
            $count = count($rows);
            $this->info("  {$table}: {$count} row(s) migrated");
            $migrated++;
        }

        $this->newLine();
        $this->info("Done. Tables migrated: {$migrated}, skipped: {$skipped}.");

        return Command::SUCCESS;
    }

    private function truncateTable(string $table): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table($table)->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
            DB::table($table)->truncate();
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::table($table)->truncate();
        }
    }
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
vendor/bin/phpunit tests/Feature/MigrateFromSqliteTest.php
```

Expected: all 5 tests pass.

- [ ] **Step 5: Run the full test suite**

```bash
vendor/bin/phpunit
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/MigrateFromSqliteCommand.php tests/Feature/MigrateFromSqliteTest.php
git commit -m "🎇 Add docket:migrate-from-sqlite command"
```

---

## Task 4: Replace CI workflows

**Files:**
- Delete: `.github/workflows/laravel.yml`
- Delete: `.github/workflows/auto-tag-release.yml`
- Delete: `.github/workflows/automerge.yml`
- Delete: `.github/workflows/manage_artifacts.yml`
- Create: `.github/workflows/ci.yml`
- Create: `.github/workflows/release.yml`
- Rewrite: `.github/workflows/dependabot-make-release.yml`

- [ ] **Step 1: Remove old workflows**

```bash
git rm .github/workflows/laravel.yml \
       .github/workflows/auto-tag-release.yml \
       .github/workflows/automerge.yml \
       .github/workflows/manage_artifacts.yml
```

- [ ] **Step 2: Create ci.yml**

Create `.github/workflows/ci.yml`:

```yaml
name: CI

on:
  push:
    branches: [dependabot-updates]
  pull_request:
    branches: ['**']
    types: [opened, synchronize, reopened]
  workflow_dispatch:
  workflow_call:
    inputs:
      tag:
        description: 'Release tag to build and deploy'
        type: string
        required: true

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  test:
    runs-on: ubuntu-latest
    permissions:
      contents: read

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          tools: composer:v2

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: 'npm'

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: ~/.composer/cache
          key: ${{ runner.os }}-composer-${{ hashFiles('composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Install PHP dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Install Node dependencies
        run: npm ci

      - name: Copy environment file
        run: cp .env.example .env

      - name: Generate application key
        run: php artisan key:generate

      - name: Run migrations
        run: php artisan migrate --force
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: database/database.sqlite

      - name: Build assets
        run: npm run build

      - name: Run tests
        run: vendor/bin/phpunit

  build-and-push:
    needs: [test]
    runs-on: ubuntu-latest
    if: |
      github.ref != 'refs/heads/dependabot-updates' &&
      (github.event_name != 'pull_request' || github.event.pull_request.head.repo.full_name == github.repository)
    permissions:
      contents: read
      packages: write

    steps:
      - name: Checkout
        uses: actions/checkout@v4
        with:
          ref: ${{ inputs.tag || github.ref }}

      - name: Log in to GitHub Container Registry
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=semver,pattern={{version}},value=${{ inputs.tag || github.ref_name }},enable=${{ inputs.tag != '' || startsWith(github.ref, 'refs/tags/v') }}
            type=semver,pattern={{major}}.{{minor}},value=${{ inputs.tag || github.ref_name }},enable=${{ inputs.tag != '' || startsWith(github.ref, 'refs/tags/v') }}
            type=sha
            type=raw,value=latest,enable=${{ inputs.tag != '' || startsWith(github.ref, 'refs/tags/v') }}
            type=raw,value=staging,enable=${{ github.event_name == 'pull_request' }}

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Build and push
        uses: docker/build-push-action@v5
        with:
          context: .
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
          build-args: |
            APP_VERSION=${{ inputs.tag || github.ref_name }}
            APP_PR_NUMBER=${{ github.event.pull_request.number }}
            APP_BRANCH=${{ github.head_ref }}

  deploy-staging:
    needs: build-and-push
    if: github.event_name == 'pull_request'
    runs-on: ubuntu-latest
    concurrency:
      group: deploy-staging
      cancel-in-progress: true
    permissions:
      contents: read

    steps:
      - name: Deploy staging
        uses: appleboy/ssh-action@v1
        with:
          host: firth.water.gkhs.net
          username: docket
          key: ${{ secrets.FIRTH_SSH_KEY }}
          script: |
            set -euo pipefail
            cd /home/docker/docket-staging
            timeout 300 docker compose pull || {
              echo "ERROR: docker compose pull failed or timed out after 300s"
              exit 1
            }
            docker compose up -d || {
              echo "ERROR: docker compose up -d failed"
              docker compose ps
              docker compose logs --tail=50
              exit 1
            }
            for i in $(seq 1 30); do
              if docker compose exec -T app php artisan --version > /dev/null 2>&1; then
                echo "Container ready after ${i} attempt(s)"
                break
              fi
              if [ "$i" -eq 30 ]; then
                echo "ERROR: Container failed to become ready after 30 attempts"
                docker compose logs --tail=50 app
                exit 1
              fi
              echo "Attempt ${i}/30: container not ready, waiting..."
              sleep 2
            done
            rm -rf public/build/assets && mkdir -p public/build
            docker compose cp app:/var/www/html/public/build/assets public/build/

  deploy-production:
    needs: build-and-push
    if: inputs.tag != ''
    runs-on: ubuntu-latest
    concurrency:
      group: deploy-production
      cancel-in-progress: false
    permissions:
      contents: read

    steps:
      - name: Deploy production
        uses: appleboy/ssh-action@v1
        with:
          host: firth.water.gkhs.net
          username: docket
          key: ${{ secrets.FIRTH_SSH_KEY }}
          script: |
            set -euo pipefail
            cd /home/docker/docket
            timeout 300 docker compose pull || {
              echo "ERROR: docker compose pull failed or timed out after 300s"
              exit 1
            }
            docker compose up -d || {
              echo "ERROR: docker compose up -d failed"
              docker compose ps
              docker compose logs --tail=50
              exit 1
            }
            for i in $(seq 1 30); do
              if docker compose exec -T app php artisan --version > /dev/null 2>&1; then
                echo "Container ready after ${i} attempt(s)"
                break
              fi
              if [ "$i" -eq 30 ]; then
                echo "ERROR: Container failed to become ready after 30 attempts"
                docker compose logs --tail=50 app
                exit 1
              fi
              echo "Attempt ${i}/30: container not ready, waiting..."
              sleep 2
            done
            rm -rf public/build/assets && mkdir -p public/build
            docker compose cp app:/var/www/html/public/build/assets public/build/
```

- [ ] **Step 3: Create release.yml**

Create `.github/workflows/release.yml` (identical to Sprouter's — copy verbatim):

```yaml
name: Release

on:
  workflow_dispatch:
    inputs:
      version_bump:
        description: 'Version bump type'
        required: true
        type: choice
        options:
          - patch
          - minor
          - major
  workflow_call:
    inputs:
      version_bump:
        type: string
        description: 'Version bump type'
        required: true

jobs:
  tag:
    runs-on: ubuntu-latest
    permissions:
      contents: write
    outputs:
      next_version: ${{ steps.next_version.outputs.next_version }}

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4
        with:
          fetch-depth: 0
          ref: main

      - name: Get latest tag
        id: get_tag
        run: |
          set -euo pipefail
          LATEST_TAG=$(git tag -l "v*.*.*" | sort -V | tail -n 1)
          if [ -z "$LATEST_TAG" ]; then
            LATEST_TAG="v0.0.0"
            echo "No existing tags found, starting from $LATEST_TAG"
          else
            echo "Latest tag: $LATEST_TAG"
          fi
          echo "latest_tag=$LATEST_TAG" >> "$GITHUB_OUTPUT"

      - name: Calculate next version
        id: next_version
        env:
          LATEST_TAG: ${{ steps.get_tag.outputs.latest_tag }}
          VERSION_BUMP: ${{ inputs.version_bump }}
        run: |
          set -euo pipefail
          VERSION=${LATEST_TAG#v}
          IFS='.' read -r MAJOR MINOR PATCH <<< "$VERSION"
          case "$VERSION_BUMP" in
            major) MAJOR=$((MAJOR + 1)); MINOR=0; PATCH=0 ;;
            minor) MINOR=$((MINOR + 1)); PATCH=0 ;;
            patch) PATCH=$((PATCH + 1)) ;;
            *) echo "Invalid version_bump: $VERSION_BUMP"; exit 1 ;;
          esac
          NEXT_VERSION="v${MAJOR}.${MINOR}.${PATCH}"
          echo "Next version: $NEXT_VERSION"
          echo "next_version=$NEXT_VERSION" >> "$GITHUB_OUTPUT"

      - name: Create and push tag
        env:
          NEXT_VERSION: ${{ steps.next_version.outputs.next_version }}
        run: |
          set -euo pipefail
          git config user.name "github-actions[bot]"
          git config user.email "github-actions[bot]@users.noreply.github.com"
          LS_REMOTE_OUTPUT=$(git ls-remote --tags origin "$NEXT_VERSION")
          if echo "$LS_REMOTE_OUTPUT" | grep -qF "refs/tags/$NEXT_VERSION"; then
            echo "Tag $NEXT_VERSION already exists, aborting"
            exit 1
          fi
          git tag -a "$NEXT_VERSION" -m "Release $NEXT_VERSION"
          git push origin "$NEXT_VERSION"
          echo "Created and pushed tag: $NEXT_VERSION"

      - name: Generate release notes
        id: release_notes
        env:
          LATEST_TAG: ${{ steps.get_tag.outputs.latest_tag }}
          NEXT_VERSION: ${{ steps.next_version.outputs.next_version }}
          REPOSITORY: ${{ github.repository }}
        run: |
          set -euo pipefail
          if [ "$LATEST_TAG" == "v0.0.0" ]; then
            COMMITS=$(git log --pretty=format:"- %s (%h)" --no-merges)
          else
            if ! git rev-parse --verify "$LATEST_TAG" > /dev/null 2>&1; then
              echo "ERROR: Tag $LATEST_TAG not found in local history"
              exit 1
            fi
            COMMITS=$(git log "${LATEST_TAG}..HEAD" --pretty=format:"- %s (%h)" --no-merges)
          fi
          [ -z "$COMMITS" ] && COMMITS="No notable changes."
          TAG_SUFFIX="${NEXT_VERSION#v}"
          {
            printf '## What'\''s Changed\n\n'
            printf '%s\n' "${COMMITS}"
            printf '\n## Docker Image\n\n'
            printf '```bash\n'
            printf 'docker pull ghcr.io/%s:%s\n' "${REPOSITORY}" "${TAG_SUFFIX}"
            printf 'docker pull ghcr.io/%s:latest\n' "${REPOSITORY}"
            printf '```\n\n'
            printf '**Full Changelog**: https://github.com/%s/compare/%s...%s\n' \
              "${REPOSITORY}" "${LATEST_TAG}" "${NEXT_VERSION}"
          } > release_notes.md

      - name: Create GitHub Release
        uses: softprops/action-gh-release@v3
        with:
          tag_name: ${{ steps.next_version.outputs.next_version }}
          name: Release ${{ steps.next_version.outputs.next_version }}
          body_path: release_notes.md
          draft: false
          prerelease: false

  ci:
    needs: tag
    uses: ./.github/workflows/ci.yml
    permissions:
      contents: read
      packages: write
    with:
      tag: ${{ needs.tag.outputs.next_version }}
    secrets: inherit # pragma: allowlist secret
```

- [ ] **Step 4: Rewrite dependabot-make-release.yml**

Replace the entire contents of `.github/workflows/dependabot-make-release.yml` with the Sprouter version, changing only the `uses:` reference in `create-release`:

```yaml
name: "[Dependabot] Make Release"

on:
  schedule:
    - cron: "0 3 * * 1" # Monday 3am UTC
  workflow_dispatch:
    inputs:
      merge_dependabot:
        description: "Merge dependabot-updates into main first"
        required: false
        default: true
        type: boolean
      version_bump:
        description: "Version bump type"
        required: true
        default: "patch"
        type: choice
        options:
          - patch
          - minor
          - major

permissions: {}

jobs:
  check-for-updates:
    runs-on: ubuntu-latest
    permissions:
      contents: read
    outputs:
      has_updates: ${{ steps.check.outputs.has_updates }}
      version_bump: ${{ steps.params.outputs.version_bump }}

    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0

      - name: Resolve parameters
        id: params
        run: |
          if [ "${{ github.event_name }}" = "schedule" ]; then
            echo "version_bump=patch" >> "$GITHUB_OUTPUT"
          else
            echo "version_bump=${{ inputs.version_bump }}" >> "$GITHUB_OUTPUT"
          fi

      - name: Check if dependabot-updates is ahead of main
        id: check
        run: |
          git fetch origin
          if ! git ls-remote --exit-code origin dependabot-updates > /dev/null 2>&1; then
            echo "dependabot-updates branch does not exist, skipping release"
            echo "has_updates=false" >> "$GITHUB_OUTPUT"
            exit 0
          fi
          AHEAD=$(git rev-list --count origin/main..origin/dependabot-updates)
          echo "dependabot-updates is $AHEAD commits ahead of main"
          if [ "$AHEAD" -gt 0 ]; then
            echo "has_updates=true" >> "$GITHUB_OUTPUT"
          else
            echo "No new commits in dependabot-updates, skipping release"
            echo "has_updates=false" >> "$GITHUB_OUTPUT"
          fi

  merge-dependabot:
    needs: check-for-updates
    if: |
      needs.check-for-updates.outputs.has_updates == 'true' &&
      (github.event_name == 'schedule' || inputs.merge_dependabot == true)
    runs-on: ubuntu-latest
    permissions:
      contents: write
      pull-requests: write
      checks: read

    steps:
      - name: Merge dependabot-updates PR into main
        env:
          GH_TOKEN: ${{ github.token }}
          GH_REPO: ${{ github.repository }}
        run: |
          gh pr ready dependabot-updates
          gh pr checks dependabot-updates --watch --fail-fast
          gh pr merge dependabot-updates --merge

  create-release:
    needs: [check-for-updates, merge-dependabot]
    if: |
      !failure() && !cancelled() &&
      (github.event_name == 'workflow_dispatch' || needs.check-for-updates.outputs.has_updates == 'true')
    permissions:
      contents: write
      packages: write
    uses: aquarion/docket/.github/workflows/release.yml@main
    with:
      version_bump: ${{ needs.check-for-updates.outputs.version_bump }}
    secrets: inherit # pragma: allowlist secret
```

- [ ] **Step 5: Validate workflow syntax**

```bash
# Install actionlint if not present: brew install actionlint / apt install actionlint
actionlint .github/workflows/ci.yml .github/workflows/release.yml .github/workflows/dependabot-make-release.yml
```

Expected: no errors. If actionlint is not available, review the YAML for indentation errors manually.

- [ ] **Step 6: Commit**

```bash
git add .github/workflows/
git commit -m "⚙️ Replace CI with Sprouter-style build/push/deploy workflows"
```

---

## Task 5: autopelago — fix role and add docket config

**Important:** Work on a new branch in the autopelago repo. Do not commit to main.

**Files (in `../autopelago`):**
- Modify: `roles/firth_laravel_app/tasks/app.yml`
- Modify: `roles/firth_laravel_app/tasks/staging.yml`
- Modify: `host_vars/firth.water.gkhs.net/laravel_apps.yml`

- [ ] **Step 1: Create a feature branch in autopelago**

```bash
cd ../autopelago
git checkout main && git pull
git checkout -b feature/add-docket
```

- [ ] **Step 2: Add additional_env to fla_ctx in app.yml**

In `roles/firth_laravel_app/tasks/app.yml`, in the `set_fact` task that builds `fla_ctx`, add `additional_env` after the `mail` line:

```yaml
      mail: "{{ firth_laravel_app_item.mail | default({}) }}"
      additional_env: "{{ firth_laravel_app_item.additional_env | default({}) }}"
```

- [ ] **Step 3: Add additional_env to staging context in staging.yml**

In `roles/firth_laravel_app/tasks/staging.yml`, in the `set_fact` task that rebuilds `fla_ctx` for staging, add `additional_env` after the `mail` line:

```yaml
      mail: "{{ fla.mail | default({}) }}"
      additional_env: "{{ fla.staging.additional_env | default(fla.additional_env | default({})) }}"
```

This means staging inherits production's `additional_env` unless staging overrides it.

- [ ] **Step 4: Add docket to laravel_apps.yml**

In `host_vars/firth.water.gkhs.net/laravel_apps.yml`, append to the `firth_laravel_app_apps` list:

```yaml
  - name: docket
    image: ghcr.io/aquarion/docket
    image_tag: latest
    backend: fpm
    port: 9003
    server_name: docket.hubris.house
    app_url: https://docket.hubris.house
    app_key: "{{ vault_docket_app_key }}"
    app_name: Docket
    ssl_snippet: hubris
    github_repo: aquarion/docket
    github_deploy_token: "{{ laravel_apps_deploy_token_aquarion }}"
    ghcr_username: "{{ ghcr_username }}"
    ghcr_token: "{{ ghcr_token }}"
    mysql:
      db_name: docket
      db_user: docket
      db_password: "{{ vault_docket_db_password }}"
    redis:
      username: docket
      password: "{{ vault_docket_redis_password }}"
    additional_env:
      MY_LAT: "{{ vault_docket_my_lat }}"
      MY_LON: "{{ vault_docket_my_lon }}"
      MAPBOX_API_TOKEN: "{{ vault_docket_mapbox_token }}"
      GOOGLE_CREDENTIALS_PATH: google/credentials.json
      GOOGLE_REDIRECT_URI: https://docket.hubris.house/token
      GOOGLE_DEFAULT_ACCOUNT: "{{ vault_docket_google_default_account }}"
      CALENDAR_CACHE_TTL: "900"
      GCAL_HOLIDAYS_SRC: "{{ vault_docket_gcal_holidays_src }}"
      GCAL_WORK_SRC: "{{ vault_docket_gcal_work_src }}"
      GCAL_PERSONAL_SRC: "{{ vault_docket_gcal_personal_src }}"
      GCAL_FAMILY_SRC: "{{ vault_docket_gcal_family_src }}"
      ICAL_WORK_URL: "{{ vault_docket_ical_work_url }}"
      ICAL_BIRTHDAYS_URL: "{{ vault_docket_ical_birthdays_url }}"
      ICAL_DEADLINES_URL: "{{ vault_docket_ical_deadlines_url }}"
    staging:
      image_tag: staging
      port: 9004
      server_name: docket.istic.dev
      app_url: https://docket.istic.dev
      app_key: "{{ vault_docket_staging_app_key }}"
      ssl_snippet: istic_dev
      mysql:
        db_name: docket_staging
        db_user: docket_staging
        db_password: "{{ vault_docket_staging_db_password }}"
      redis:
        username: docket_staging
        password: "{{ vault_docket_staging_redis_password }}"
```

- [ ] **Step 5: Add vault entries**

Open the firth host vault and add the following keys. Use `ansible-vault edit host_vars/firth.water.gkhs.net/vault.yml` (or wherever firth's vault lives):

```yaml
vault_docket_app_key: "base64:..."          # generate: php artisan key:generate --show
vault_docket_staging_app_key: "base64:..."  # generate: php artisan key:generate --show
vault_docket_db_password: "..."             # strong random password
vault_docket_staging_db_password: "..."     # strong random password
vault_docket_redis_password: "..."          # strong random password
vault_docket_staging_redis_password: "..."  # strong random password
vault_docket_my_lat: "..."                  # latitude from existing .env
vault_docket_my_lon: "..."                  # longitude from existing .env
vault_docket_mapbox_token: "..."            # Mapbox API token from existing .env
vault_docket_google_default_account: "..."  # value from existing .env
vault_docket_gcal_holidays_src: "..."       # from existing .env
vault_docket_gcal_work_src: "..."           # from existing .env
vault_docket_gcal_personal_src: "..."       # from existing .env
vault_docket_gcal_family_src: "..."         # from existing .env
vault_docket_ical_work_url: "..."           # from existing .env
vault_docket_ical_birthdays_url: "..."      # from existing .env
vault_docket_ical_deadlines_url: "..."      # from existing .env
```

- [ ] **Step 6: Validate YAML syntax**

```bash
cd ../autopelago
ansible-lint roles/firth_laravel_app/tasks/app.yml roles/firth_laravel_app/tasks/staging.yml
yamllint host_vars/firth.water.gkhs.net/laravel_apps.yml
```

Expected: no errors.

- [ ] **Step 7: Commit in autopelago**

```bash
git add roles/firth_laravel_app/tasks/app.yml \
        roles/firth_laravel_app/tasks/staging.yml \
        host_vars/firth.water.gkhs.net/laravel_apps.yml
git commit -m "🎇 Add docket deployment config and additional_env support"
```

---

## Post-Deployment Steps (manual, after Ansible runs)

These steps are done once on the server after the first successful Ansible deploy:

**1. Copy Google credentials into the storage volume:**
```bash
# SSH to firth as docket user
ssh docket@firth.water.gkhs.net
cd /home/docker/docket
docker compose exec app mkdir -p storage/app/google
docker compose cp /path/to/credentials.json app:/var/www/html/storage/app/google/credentials.json
```

**2. Run the SQLite → MySQL data migration** (if migrating an existing installation):
```bash
# Copy your local database.sqlite to the server, then:
docker compose cp database.sqlite app:/var/www/html/database/database.sqlite
docker compose exec -T app php artisan docket:migrate-from-sqlite
```
