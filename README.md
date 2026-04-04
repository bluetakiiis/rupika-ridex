# Rentals App

Rental platform "RIDEX" with public booking flow, user dashboard, admin console, GPS tracking, and payment gateway (Khalti) hooks. UI flows come from the provided Website flow docx/pdf.

## Features

- Public: landing/hero search, vehicle catalog with filters, vehicle detail with status badge and booking CTA, booking form → confirm → receipt/history.
- Auth: login/register/forgot/reset, profile edit.
- Admin: login, dashboard with line (car/bike/luxury) and pie (rental share) charts, fleet CRUD + status changes, all bookings table + detail modal, GPS live tracking/history.
- Payments: Khalti initiation/verification, pay-on-arrival path, receipts.
- APIs: JSON endpoints for vehicles, bookings, auth, payments, GPS.
- Ops: cron jobs for payments verify, GPS cleanup, stats, reminders; migrations/seeds for setup.

## Requirements

- PHP 8.1+
- Composer
- MySQL
- Optional: MQTT/WebSocket broker for live GPS

## Setup

1. Install deps: `composer install`
2. Copy env: `cp config/env.example .env` (fill DB, base URL, Khalti keys, GPS broker)
3. Migrate DB: `php bin/migrate.php`
4. Seed sample data: `php bin/seed.php` (optional)
5. Serve (dev): `php -S localhost:8000 -t public`

## Render Free Deployment (Single Service)

If you cannot pay for a separate Render DB service, deploy only the web service with Docker and run MariaDB inside the same container.

- This repo now supports embedded DB mode by default using `docker/entrypoint.sh`.
- Keep `USE_EMBEDDED_DB=1` (default).
- Set these web-service env vars on Render:
  - `DB_NAME=ridex_db`
  - `DB_USER=ridex_app`
  - `DB_PASS=<your_password>`
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `BASE_URL=/`
- On container boot, it will:
  - start MariaDB locally,
  - run `php bin/migrate.php`,
  - run `php bin/import_db_snapshot.php --if-missing-ok` (unless `APPLY_DB_SNAPSHOT_ON_BOOT=0`),
  - run `php bin/seed.php`,
  - start Apache.

Notes:
- This is suitable for demos/school projects.
- Container storage is ephemeral on free tier, so DB data can reset when the instance is replaced.
- For persistent production data, use a managed DB with persistent storage.

## Project Layout (high level)

- `public/` entrypoint and assets (css/js/uploads)
- `src/Controllers/` web controllers; `src/Controllers/Admin/`, `Api/`, `Middleware/`
- `src/Views/` blade-less PHP views (public, booking, user, admin, partials)
- `src/Templates/` layouts + email/PDF templates
- `src/Models/` data access (users, vehicles, bookings, payments, gps_logs, categories)
- `config/` app/env/db/routes, khalti, gps
- `bin/` CLI (migrate, seed, cron scripts)
- `charts/` Chart.js configs (admin line + pie)
- `migrations/` SQL schema files
- `logs/`, `var/` runtime outputs

## Cron/Background (optional but recommended)

- `bin/cron_verify_payments.php`: reconcile gateway callbacks
- `bin/cron_cleanup_gps.php`: purge old gps_logs
- `bin/cron_expire_bookings.php`, `bin/cron_generate_stats.php`, `bin/cron_send_reminders.php`: optional automation

## Notes

- Routes and controllers are scaffolded; wire `config/routes.php` to the current flows.
- Keep status enums (available/reserved/on trip/maintenance/overdue) in sync across UI, constants, and DB.

## Vehicle JSON Sync

- Vehicles now sync bidirectionally between DB and JSON files under `data/vehicles-json/`:
  - `cars.json`
  - `bikes.json`
  - `luxury.json`
- Public requests trigger sync automatically in `public/index.php` before and after page handling.
- Keep these JSON files committed so Render deployments use the same vehicle inventory as local.
- Manual/cron sync command:
  - `php bin/sync_vehicles_json.php`
- Default conflict rule: latest update wins across JSON and DB.
- JSON edits are detected even when `updated_at` is not manually changed (row hash + file mtime fallback).
- If a category JSON file is malformed, sync now fails with an explicit error instead of silently reverting file content.
- Optional conflict-bias modes:
  - Force JSON winner: `php bin/sync_vehicles_json.php --prefer-json` (or `--force-json`)
  - `php bin/sync_vehicles_json.php --prefer-db-timestamps`
  - `php bin/sync_vehicles_json.php --prefer-db` (alias)

## Full Data Snapshot For Render Parity

- Commit-tracked DB snapshot file: `data/deploy/db_snapshot.sql`
- Export current DB into snapshot:
  - `php bin/export_db_snapshot.php`
- Import snapshot into DB:
  - `php bin/import_db_snapshot.php`
- To keep Render deploy data identical to your latest committed local DB, run before commit:
  - `php bin/sync_vehicles_json.php --prefer-db-timestamps`
  - `php bin/export_db_snapshot.php`
- A ready pre-commit hook is provided at `.githooks/pre-commit` to automate this for every commit.
- Enable it once per clone:
  - `git config core.hooksPath .githooks`
