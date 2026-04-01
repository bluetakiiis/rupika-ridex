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
