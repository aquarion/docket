# Docker Deployment Design

**Date:** 2026-06-02
**Status:** Approved

## Overview

Migrate Docket to a production Docker deployment using the same model as Sprouter, deployed by the `firth_laravel_app` Ansible role in autopelago. This involves replacing the outdated Dockerfile, switching the database from SQLite to MySQL, adding a data migration command, updating CI/CD, and registering Docket in autopelago.

## Decisions

| Decision | Choice | Reason |
|---|---|---|
| PHP serving | FPM (`php:8.4-fpm-alpine`) | Simpler than Octane; no new dep |
| Database | MySQL (external via host) | Fits firth_laravel_app model; avoids SQLite write-concurrency limits |
| Local dev | Laravel Sail (keep, add MySQL) | Existing dev workflow preserved |
| Queue worker | None | No queued jobs in use |
| Production domain | docket.hubris.house | |
| Staging domain | docket.istic.dev | |

---

## 1. Dockerfile (rewrite)

Replace `Dockerfile` with a multi-stage FPM build.

**Stage 1 — node-deps** (`node:22-alpine`):
- Copy `package.json` / `package-lock.json`, run `npm ci`

**Stage 2 — production** (`php:8.4-fpm-alpine`):
- Install system deps: `git`, `unzip`, `nodejs`, `npm`
- Install PHP extensions: `pdo_mysql`, `redis`, `pcntl`, `opcache`
- Copy composer from `composer:2` image
- `composer install --no-dev --optimize-autoloader --no-scripts`
- Copy node_modules from stage 1, copy source
- Create storage dirs, copy `.env.example` → `.env`, generate key, discover packages, `npm run build`, remove `.env` and `node_modules`
- Set ownership: `www-data:www-data` on `storage`, `bootstrap/cache`, `public`
- Copy `docker/entrypoint.sh`, set executable
- Set `USER www-data`
- `EXPOSE 9000`
- Build args: `APP_VERSION`, `APP_PR_NUMBER`, `APP_BRANCH` (OCI labels)
- `ENTRYPOINT ["/entrypoint.sh"]`

---

## 2. docker/entrypoint.sh

```
Validate APP_KEY is set (exit 1 if not)
mkdir -p storage dirs
php artisan config:cache
php artisan view:cache
if RUN_MIGRATIONS != "false": php artisan migrate --force
exec php-fpm
```

`RUN_MIGRATIONS` defaults to `true`. Set to `false` on additional replicas to avoid concurrent migration attempts. Deployments come from GitHub Actions (SSH → `docker compose pull && up -d`), so migrations run at container startup rather than being driven externally.

---

## 3. docker-compose.yaml (Sail — add MySQL)

Keep the existing Sail setup. Add a `mysql` service:

```yaml
mysql:
  image: mysql:8.0
  environment:
    MYSQL_DATABASE: ${DB_DATABASE:-docket}
    MYSQL_USER: ${DB_USERNAME:-docket}
    MYSQL_PASSWORD: ${DB_PASSWORD:-secret}
    MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-secret}
  volumes:
    - sail-mysql:/var/lib/mysql
  healthcheck: ...
  networks:
    - sail
```

Add `sail-mysql` to the volumes block. Add `mysql` to the `laravel.test` service's `depends_on`.

---

## 4. .env.example

Change:
```
DB_CONNECTION=sqlite
```
To:
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=docket
DB_USERNAME=docket
DB_PASSWORD=secret
```

All other Docket-specific vars (`MY_LAT`, `MY_LON`, `MAPBOX_API_TOKEN`, `GCAL_*`, `ICAL_*`, Google OAuth) remain unchanged.

---

## 5. SQLite → MySQL Migration Command

New artisan command: `docket:migrate-from-sqlite`

- Accepts an optional `--sqlite-path` option (defaults to `database/database.sqlite`)
- Runs after `php artisan migrate` (schema must already exist in MySQL)
- For each table, reads all rows from SQLite and bulk-inserts into MySQL
- Skips tables that already have data in MySQL (with `--force` flag to override)
- Prints a summary of records migrated per table

This is a one-time operational command; it can remain in the codebase afterwards as a safety net.

---

## 6. GitHub Actions CI

### Workflows removed
- `laravel.yml` — replaced by tests in `ci.yml`
- `auto-tag-release.yml` — replaced by `release.yml`
- `automerge.yml` — replaced by `dependabot-make-release.yml`
- `manage_artifacts.yml` — removed

### Workflows added

**`ci.yml`** (modelled on Sprouter's, adapted for FPM):
- Triggers: `push` to `dependabot-updates`, `pull_request` to any branch, `workflow_dispatch`, `workflow_call` with `tag` input
- Jobs:
  1. `test` — PHP 8.4 + Node 22; composer install with `actions/cache` on `~/.composer/cache`; npm ci with `actions/setup-node` built-in cache (`cache: 'npm'`); migrate (SQLite for CI), run phpunit
  2. `build-and-push` — needs `test`; skips on dependabot-updates push and external forks; logs into GHCR, extracts metadata (semver tags + `latest` on release, `staging` on PR, sha always), builds and pushes with GHA cache
  3. `deploy-staging` — needs `build-and-push`, on `pull_request` only; SSH to firth, `docker compose pull && up -d`, wait for container ready, copy Vite assets from container (migrations run in entrypoint)
  4. `deploy-production` — needs `build-and-push`, on `workflow_call` with tag only; same SSH pattern as staging

**`release.yml`** — copy from Sprouter verbatim (calculates next semver, creates tag, calls `ci.yml`)

**`dependabot-make-release.yml`** — copy from Sprouter, updating the `uses:` reference from `aquarion/sprouter/.github/workflows/release.yml@main` to `aquarion/docket/.github/workflows/release.yml@main` (weekly Monday 3am, checks for updates on `dependabot-updates`, merges, calls `release.yml`)

### Workflows kept
- `auto-rebase-dependabot.yml` — unchanged

---

## 7. autopelago — laravel_apps.yml

Add to `firth_laravel_app_apps`:

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

New vault entries required (in firth host vault):
- `vault_docket_app_key`, `vault_docket_staging_app_key`
- `vault_docket_db_password`, `vault_docket_staging_db_password`
- `vault_docket_redis_password`, `vault_docket_staging_redis_password`
- `vault_docket_my_lat`, `vault_docket_my_lon`
- `vault_docket_mapbox_token`
- `vault_docket_google_default_account`
- `vault_docket_gcal_*`, `vault_docket_ical_*` (as needed)

---

## 8. Post-Deployment Manual Step

The app reads Google OAuth credentials from `storage/app/google/credentials_{account}.json`. This file is not managed by Ansible. After first deployment, manually copy the credentials JSON into the Docker storage volume on firth:

```bash
docker compose -p docket cp credentials.json app:/var/www/html/storage/app/google/credentials.json
```

---

## Build Sequence

1. `docket`: Dockerfile, entrypoint.sh, docker-compose.yaml, .env.example
2. `docket`: `docket:migrate-from-sqlite` artisan command
3. `docket`: Replace/add CI workflows
4. `autopelago`: Add docket to laravel_apps.yml, add vault entries
5. Post-deploy: copy Google credentials into storage volume, run `docket:migrate-from-sqlite`
