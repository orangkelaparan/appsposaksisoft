# Security Controls

## Application controls

| Risk | Control |
| --- | --- |
| Credential attack | Login endpoint is rate limited; passwords are hashed with Laravel’s password hashing service; inactive accounts cannot establish a session. |
| Session fixation | Login regenerates the session identifier; logout invalidates the session and regenerates the CSRF token. |
| Unauthorized access | `pos.auth` verifies an active server-side user session. Sensitive writes make a server-side granular-permission decision; UI visibility is not trusted as authorization. |
| Cross-site request forgery | Browser state-changing routes remain in Laravel’s `web` middleware group and require a CSRF token. |
| SQL injection / mass assignment | Controllers validate input and use Laravel’s query builder parameter binding; public input is never concatenated into SQL. |
| Stock race condition | `InventoryService` locks the relevant balance row, validates stock, writes ledger history, and relies on a surrounding database transaction. |
| Financial alteration | Completed sales retain a snapshot of items, price, cost, discounts, and payments. Corrections use returns rather than destructive editing. |
| Operational accountability | Login, logout, product creation, purchase receiving, completed sales, returns, and register actions are written to `audit_logs`. |

## Secret handling

`.env` is ignored by Git. Store `APP_KEY`, database credentials, `ADMIN_PASSWORD`, Cloudflare credentials, GitHub deployment keys, and any third-party token only in the server environment or repository secret manager. Never place those values in source files, screenshots, issue comments, command history, or documentation.

## Production baseline

Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://pos.aksisoft.web.id`, secure database credentials, and an application-specific `ADMIN_PASSWORD` before the first seeding run. Use HTTPS, restrict filesystem ownership to the web process where needed, update PHP/Laravel dependencies routinely, and rotate any credentials that have been pasted into a chat or shell.

## Audit review

Audit logs should be accessible only to authorized owners, finance staff, and auditors. Treat logs as append-only operational evidence; archive according to the business retention policy.
