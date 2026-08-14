# AksiSoft POS

**AksiSoft POS** is a Laravel-based Point of Sale platform for Indonesian retail operations. It combines a keyboard-friendly cashier workspace with product catalogues, a traceable inventory ledger, purchasing and receiving, customers and suppliers, cash-register sessions, operational reports, server-side access controls, and audit history.

> **Design intent:** completed sales are never silently edited. Stock and financial effects are written within database transactions, with a ledger and audit entry that preserve the operational history.

## Implemented scope

| Area | Implemented capability |
| --- | --- |
| Authentication | Username/email login, session regeneration, rate limiting, inactive-user handling, audit events, secure logout. |
| Access control | Seeded roles and granular permissions; sensitive write actions validate authorization server-side. |
| POS | Barcode/SKU/name lookup, product grid, dynamic cart, quantity controls, customer selection, fixed cart discount, cash/QRIS/card choice, tendered cash, change calculation, thermal receipt. |
| Catalogue | Products with SKU, barcode, cost, selling price, supplier, category, brand, unit, and low-stock threshold. |
| Inventory | Per-warehouse stock, weighted-average-ready cost, row locking, immutable movement ledger, low/out-of-stock state. |
| Purchasing | Approved purchase order creation and receiving of outstanding quantities, with automatic stock/ledger posting. |
| Sales and returns | Atomic sale, line-item history, payment record, stock reduction, audit record, receipt, and controlled item return service. |
| Cash register | Opening balance, open session, expected cash calculation, close count, and variance recording. |
| Reporting | Date-range revenue, transaction, discount, tax, stock valuation, product, and payment summaries. |
| Operations | `/health` JSON endpoint, production cache commands, GitHub Actions test/build gate, Apache deployment guide. |

## Technology

The application uses **Laravel 13**, **PHP 8.3+**, Blade templates, Tailwind CSS 4, Vite, and MySQL 8/MariaDB in production. Laravel 13 requires PHP 8.3 or later, and its deployment guidance recommends serving the application from the `public` directory, keeping writable `storage` and `bootstrap/cache` directories, and disabling debug mode in production.[1] [2]

## Local installation

Install PHP 8.3+, Composer, Node.js, and an SQLite or MySQL database. Copy the environment template and use a non-committed password only in your local environment.

```bash
cp .env.example .env
php artisan key:generate

# SQLite development example
mkdir -p database
touch database/database.sqlite
# Set DB_CONNECTION=sqlite and DB_DATABASE=/absolute/path/to/database.sqlite in .env

# Required only for seeding the first administrator
# Set ADMIN_PASSWORD to a strong local password in .env or the command environment.
# Set DEMO_SEED=true to create Indonesian demo master data and 50 products.

composer install
npm install
npm run build
php artisan migrate --seed
php artisan serve
```

Open `http://127.0.0.1:8000`. The seed account uses `ADMIN_EMAIL` / `ADMIN_USERNAME` from the environment; its password is **only** the value of `ADMIN_PASSWORD`. The database seeder intentionally stops when that variable is absent, preventing a production password from being embedded in source control.

## Test and quality gates

```bash
php artisan test --compact
./vendor/bin/pint --test
npm run build
```

The feature suite verifies the important sale invariant: a successful checkout creates the sale, sale item, payment, inventory ledger, and audit entry; an insufficient-stock checkout returns an error and rolls the database work back.

## Production deployment: Apache 2 + PHP-FPM

Use a release directory outside the web root where possible. The Apache `DocumentRoot` must point to the application’s `public` directory, never the project root.[1]

```bash
# Example only: use the discovered deployment path on the target server.
cd /var/www/pos.aksisoft.web.id
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build

# Configure .env with production values; do not commit it.
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan optimize
chown -R www-data:www-data storage bootstrap/cache
```

An Apache site file should use `public/` as `DocumentRoot`, pass PHP requests to the discovered PHP-FPM socket, restrict project-root access, and be tested with `apache2ctl configtest` before reload. See [`docs/deployment.md`](docs/deployment.md) for the supplied template and operational steps.

## Scheduler and queue

The application ships with the database jobs migration and is ready for a production queue worker when reports, exports, imports, or notification jobs are introduced. Configure the scheduler once per minute:

```cron
* * * * * cd /var/www/pos.aksisoft.web.id && php artisan schedule:run >> /dev/null 2>&1
```

Use a systemd or Supervisor worker when `QUEUE_CONNECTION=database` is enabled. Restart long-running workers after each deployment.

## Security notes

The repository never contains `.env`, private keys, API tokens, database passwords, or production administrator passwords. Login attempts are throttled; state-changing browser requests use CSRF protection; input is validated server-side; passwords use Laravel hashing; and sensitive writes are recorded in `audit_logs`. See [`docs/security.md`](docs/security.md).

## Documentation

| Document | Purpose |
| --- | --- |
| [`docs/architecture.md`](docs/architecture.md) | Modules, service boundaries, and transaction guarantees. |
| [`docs/database.md`](docs/database.md) | Major tables, relationships, ledger rules, and indexes. |
| [`docs/pos-workflow.md`](docs/pos-workflow.md) | Cashier, purchasing, receiving, return, and register flows. |
| [`docs/permissions.md`](docs/permissions.md) | RBAC roles and granular permission catalogue. |
| [`docs/deployment.md`](docs/deployment.md) | VPS discovery, Apache, HTTPS, queue, backup, and rollback procedure. |
| [`docs/security.md`](docs/security.md) | Security posture and operational controls. |

## References

[1] [Laravel 13 — Installation: directory configuration](https://laravel.com/docs/13.x/installation)

[2] [Laravel 13 — Deployment: requirements, permissions, optimization, debug mode, and health route](https://laravel.com/docs/13.x/deployment)
