# Production Deployment Guide

## Preconditions

The target VPS must provide PHP 8.3+, Composer, Node.js, Apache 2 with the PHP-FPM integration, MySQL/MariaDB, and an HTTPS certificate path. Confirm the live server state before changing it: operating system, disk/RAM, PHP modules, PHP-FPM socket, Apache sites, existing virtual hosts, database service, and the intended deployment directory.

> Do not use `migrate:fresh` in production. It destroys existing data.

## Environment configuration

Create `/var/www/pos.aksisoft.web.id/.env` from `.env.example`, set correct production values, and keep its owner restrictive. Required values include `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://pos.aksisoft.web.id`, MySQL credentials, `SESSION_DRIVER=database`, and a strong, non-committed `ADMIN_PASSWORD` for initial seeding only.

## Apache site template

Adapt the PHP-FPM socket and release directory to the discovered server values.

```apache
<VirtualHost *:80>
    ServerName pos.aksisoft.web.id
    DocumentRoot /var/www/pos.aksisoft.web.id/public

    <Directory /var/www/pos.aksisoft.web.id/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch "\.php$">
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost/"
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/pos.aksisoft.web.id-error.log
    CustomLog ${APACHE_LOG_DIR}/pos.aksisoft.web.id-access.log combined
</VirtualHost>
```

Back up relevant Apache files first, then create a dedicated site, enable only the required modules (`rewrite`, `proxy_fcgi`, `setenvif`), validate with `apache2ctl configtest`, and reload only after a successful test. Do not overwrite or disable unrelated sites.

## Deployment sequence

```bash
cd /var/www/pos.aksisoft.web.id
git fetch origin main
git reset --hard origin/main
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
chown -R www-data:www-data storage bootstrap/cache
sudo apache2ctl configtest && sudo systemctl reload apache2
curl -fsS https://pos.aksisoft.web.id/health
```

Use a non-root deployment user for a future automated workflow where feasible. The initial server deployment may require root only for Apache, package, permission, or system service administration.

## HTTPS and DNS

Inspect the existing Cloudflare zone and `pos` record first. Create or update a single A record only when it does not match the VPS public IPv4. Preserve the established Cloudflare proxy policy. After DNS propagation, obtain or install the site certificate, force the appropriate HTTP-to-HTTPS redirect, and check `https://pos.aksisoft.web.id/health`, asset loading, session cookies, and CSRF POST flow.

## Scheduler, queue, logs, backups

Add a single `schedule:run` cron entry. When database queues are enabled, create a systemd or Supervisor worker and restart it after deployment. Enable log rotation for Laravel and Apache logs. Configure a separate, encrypted database dump task and storage backup policy before claiming backups are active; test a restore into a non-production database.

## Rollback

Preserve the prior Git commit before a release. If a post-release health check fails, reset to the known-good commit, reinstall dependencies only if lockfile changed, rerun `php artisan optimize`, reload Apache, and verify the health endpoint. Do not roll back database migrations destructively without a pre-tested migration strategy.
