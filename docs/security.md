# Security Reference

This document provides a detailed reference for every security control implemented in or recommended for this application. It complements the high-level overview in [README.md](../README.md#api-security--authentication).

---

## Table of Contents

1. [API Authentication](#1-api-authentication)
2. [Token Lifecycle Management](#2-token-lifecycle-management)
3. [Rate Limiting](#3-rate-limiting)
4. [CORS Policy](#4-cors-policy)
5. [HTTPS & Secure Headers](#5-https--secure-headers)
6. [File Upload Security](#6-file-upload-security)
7. [Webhook Validation](#7-webhook-validation)
8. [Environment Variables & Secrets](#8-environment-variables--secrets)
9. [Credential Rotation Runbooks](#9-credential-rotation-runbooks)
10. [Access Control](#10-access-control)
11. [Database Security](#11-database-security)
12. [Redis Security](#12-redis-security)
13. [Queue & Job Security](#13-queue--job-security)
14. [Logging & Auditing](#14-logging--auditing)
15. [Dependency Security](#15-dependency-security)

---

## 1. API Authentication

### Sanctum (primary — token-based)

All REST API routes under `auth:sanctum` require a Bearer token:

```
Authorization: Bearer <plaintext-token>
```

Tokens are issued at registration and login:

```
POST /api/auth/register   { name, email, password, password_confirmation }
POST /api/auth/login      { email, password }
```

Both responses include `{ user, token }`. The token is **never** stored in plain text in the database — only a hashed copy is kept in `personal_access_tokens`.

Revoke the current token on logout:

```
POST /api/auth/logout   Authorization: Bearer <token>
```

### Passport (OAuth2 — optional)

For third-party integrations or when delegated authorization is needed, Laravel Passport provides the full OAuth2 server. See [README.md — Laravel Passport](../README.md#laravel-passport).

### Guard resolution

The `auth:sanctum` guard is active on all protected routes (see `routes/api.php`). Unauthenticated requests receive HTTP **401 Unauthorized** from Sanctum's exception handler — no stack traces or internal details are exposed.

### Token abilities (scopes)

Restrict what a token can do by issuing it with abilities:

```php
$token = $user->createToken('mobile-app', ['read:students', 'write:students']);
```

Enforce abilities in controllers:

```php
abort_unless($request->user()->tokenCan('write:students'), 403);
```

---

## 2. Token Lifecycle Management

| Event | Action |
|---|---|
| User registers | New token issued automatically |
| User logs in | New token issued, existing tokens remain valid |
| User logs out | Current token deleted |
| Password changed | Strongly recommended: revoke all tokens (`$user->tokens()->delete()`) |
| Suspected breach | Truncate all tokens system-wide; force re-login |
| Token expiry | Set `SANCTUM_EXPIRATION` (in minutes) in `.env`; `null` = never expires |

To set a 30-day expiry:

```dotenv
SANCTUM_EXPIRATION=43200
```

Then add a scheduled prune job to clean up expired tokens:

```php
Schedule::command('sanctum:prune-expired --hours=720')->daily();
```

---

## 3. Rate Limiting

### Default Laravel rate limiter

Laravel applies `throttle:api` (60 requests/minute per IP) to the `api` route group by default.

### Tighter limit for authentication endpoints

Add a dedicated limiter to prevent brute-force and credential-stuffing attacks:

```php
// In bootstrap/app.php or a ServiceProvider
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('auth', function (Request $request) {
    return [
        Limit::perMinute(10)->by($request->ip()),             // IP-based
        Limit::perMinute(5)->by($request->input('email')),    // per email
    ];
});
```

Apply in `routes/api.php`:

```php
Route::middleware('throttle:auth')->group(function () {
    Route::post('auth/register', ...);
    Route::post('auth/login', ...);
});
```

### Response when rate limit is exceeded

Laravel returns HTTP **429 Too Many Requests** with a `Retry-After` header.

---

## 4. CORS Policy

Cross-Origin Resource Sharing is configured in `config/cors.php`.

### Development (permissive)

```php
'allowed_origins' => ['*'],
```

### Production (strict)

```php
'allowed_origins' => [env('FRONTEND_URL', 'https://yourapp.com')],
'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
'supports_credentials' => true,   // only if using cookie-based Sanctum
```

Set `FRONTEND_URL` in your `.env` to match the domain serving the SPA.

> **Warning:** `allowed_origins: ['*']` combined with `supports_credentials: true` is rejected by all browsers — never use both together.

---

## 5. HTTPS & Secure Headers

### Force HTTPS in production

```php
// app/Providers/AppServiceProvider.php
if ($this->app->environment('production')) {
    \Illuminate\Support\Facades\URL::forceScheme('https');
}
```

### Session cookie hardening

```dotenv
SESSION_SECURE_COOKIE=true    # only transmitted over HTTPS
SESSION_SAME_SITE=lax         # CSRF mitigation for cross-site requests
SESSION_HTTP_ONLY=true        # not accessible to JavaScript (default)
```

### Nginx security headers

Add to your `server {}` block (see [README.md — Nginx config](../README.md#6-configure-nginx)):

```nginx
add_header X-Frame-Options           "SAMEORIGIN"          always;
add_header X-Content-Type-Options    "nosniff"             always;
add_header Referrer-Policy           "strict-origin"       always;
add_header Permissions-Policy        "geolocation=()"      always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
# Restrict scripts to your own origin; adjust as needed:
add_header Content-Security-Policy   "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';" always;
```

### `fastcgi_hide_header X-Powered-By`

Already included in the sample Nginx config — prevents advertising the PHP version to attackers.

---

## 6. File Upload Security

### `ProfileController` controls

| Control | Value |
|---|---|
| MIME type whitelist | `jpeg`, `jpg`, `png`, `webp` |
| Maximum file size | 2 MB (2048 KB) |
| Filename | Laravel generates a random UUID — the original filename is **never used** |
| Storage path | `avatars/<uuid>.<ext>` |
| Old file cleanup | Previous file is deleted before the new one is stored |

### Why the filename is randomised

User-supplied filenames can contain path-traversal sequences (`../../etc/passwd`) or executable extensions that a misconfigured server might run (`shell.php`). Using Laravel's `store()` method with a random UUID eliminates this entirely.

### S3 object ACL

Export files (`exports/students/*.csv`) are stored as **private** objects on S3 — they are only accessible via a time-limited presigned URL (30 minutes). Never store exports as public S3 objects.

### Virus scanning (optional, recommended for production)

For user-uploaded files, consider scanning with ClamAV:

```bash
sudo apt install -y clamav clamav-daemon
freshclam
```

```php
$result = shell_exec("clamscan --no-summary " . escapeshellarg($request->file('avatar')->path()));
if (str_contains($result, 'FOUND')) {
    abort(422, 'File failed virus scan.');
}
```

---

## 7. Webhook Validation

See [README.md — Webhook Validation](../README.md#webhook-validation) for the full implementation.

### Summary of controls

| Control | Purpose |
|---|---|
| HMAC-SHA256 signature | Proves the payload came from the expected sender |
| Timestamp header (`X-Webhook-Timestamp`) | Prevents replay attacks (reject if > 5 minutes old) |
| `hash_equals()` for comparison | Prevents timing attacks when comparing signatures |
| Idempotency key (`X-Webhook-Event-Id`) | Prevents duplicate processing if the sender retries |
| Raw body reading (`$request->getContent()`) | Ensures the signature covers the exact bytes sent |

### `hash_equals` is mandatory

**Never** use `===` or `==` to compare HMAC signatures. These operators short-circuit and leak timing information that can be used to forge a valid signature. `hash_equals()` runs in constant time regardless of where the strings diverge.

### Supported third-party signature formats

| Provider | Header | Algorithm |
|---|---|---|
| Stripe | `Stripe-Signature` | HMAC-SHA256 with timestamp |
| GitHub | `X-Hub-Signature-256` | HMAC-SHA256 |
| Shopify | `X-Shopify-Hmac-SHA256` | HMAC-SHA256 (Base64-encoded) |
| SendGrid | `X-Twilio-Email-Event-Webhook-Signature` | ECDSA |

Always use the provider's official SDK for signature validation (e.g., `\Stripe\Webhook::constructEvent()`).

---

## 8. Environment Variables & Secrets

### What must never appear in `.env.example` or source control

- `APP_KEY` (generate on first deploy; never commit)
- `DB_PASSWORD`
- `REDIS_PASSWORD`
- `AWS_SECRET_ACCESS_KEY`
- `MAIL_PASSWORD`
- Any `*_SECRET`, `*_KEY`, or `*_TOKEN` value

`.env` is already in `.gitignore`. Ensure your IDE and Docker bind-mount configurations do not inadvertently expose it.

### Secrets in CI/CD

Store all secrets as **GitHub Actions Secrets** (`Settings → Secrets and variables → Actions`). Reference them with `${{ secrets.SECRET_NAME }}` — they are masked in logs automatically.

The CI workflow (`ci.yml`) uses only safe test values injected via `env:` — no real credentials are needed for the test suite.

### Secrets managers comparison

| Manager | Best for | Notes |
|---|---|---|
| GitHub Actions Secrets | CI/CD pipelines | Free; scoped to org/repo/environment |
| AWS Secrets Manager | EC2 / Lambda / ECS | Costs ~$0.40/secret/month; auto-rotation |
| AWS SSM Parameter Store | EC2 / Lambda | Free tier available; `SecureString` = encrypted |
| HashiCorp Vault | Multi-cloud, self-hosted | Most flexible; requires ops overhead |
| Laravel Vault (`esdc/laravel-vault`) | Laravel-native Vault integration | Community package |

### Encrypting values in the database

Use Laravel's `Crypt` facade for sensitive model fields:

```php
// In migration:
$table->text('ssn');  // store encrypted text

// In model:
protected $casts = [
    'ssn' => 'encrypted',
];
```

Encrypted casts use `APP_KEY` internally — rotating the key requires re-encrypting all data.

---

## 9. Credential Rotation Runbooks

### APP_KEY rotation

```bash
# 1. Generate a new key (print only — do not write yet)
php artisan key:generate --show

# 2. Update the key in your secrets manager / .env on all servers simultaneously
# 3. Clear and re-cache config on all instances
php artisan config:clear && php artisan config:cache

# 4. Restart PHP-FPM and queue workers
sudo systemctl reload php8.3-fpm
sudo supervisorctl restart laravel-worker:*
```

> All signed URLs (email verification links, temporary S3 download URLs) and encrypted cookies will be invalidated. Users will need to log in again.

### Database password rotation

```bash
# 1. Set the new password in MySQL
sudo mysql -e "ALTER USER 'laravel'@'localhost' IDENTIFIED BY '<new>'; FLUSH PRIVILEGES;"

# 2. Update the credential everywhere (secrets manager, .env)
# 3. Reload config and workers
php artisan config:cache
sudo systemctl reload php8.3-fpm
sudo supervisorctl restart laravel-worker:*
```

### Redis password rotation

```bash
# 1. Edit /etc/redis/redis.conf
#    Change: requirepass <old>
#    To:     requirepass <new>

# 2. Reload Redis
sudo systemctl reload redis-server

# 3. Update REDIS_PASSWORD in .env / secrets manager, then:
php artisan config:cache
sudo supervisorctl restart laravel-worker:*
```

### AWS access key rotation

```bash
# 1. Create a new key pair in IAM Console (allow brief dual-key period)
# 2. Update AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY everywhere
# 3. Verify S3 connectivity: php artisan tinker
Storage::disk('s3')->put('test.txt', 'ok');
# 4. Delete the old key in IAM Console
```

> Prefer IAM instance roles on EC2 — roles rotate automatically and have no static keys to manage.

### Sanctum token mass revocation

```bash
php artisan tinker
# Revoke all tokens for a single user:
App\Models\User::find($id)->tokens()->delete();

# Revoke ALL tokens (nuclear option — after a breach):
Laravel\Sanctum\PersonalAccessToken::truncate();
```

---

## 10. Access Control

### Horizon dashboard

`App\Providers\HorizonServiceProvider::gate()` controls who can view `/horizon`. Restrict to verified admin emails:

```php
Gate::define('viewHorizon', function ($user) {
    return in_array($user->email, config('horizon.allowed_emails', []));
});
```

Store allowed emails in `.env` to keep them out of source control:

```dotenv
HORIZON_ALLOWED_EMAILS="admin@example.com,ops@example.com"
```

### Policy-based authorization

For resource-level authorization, create Laravel Policies:

```bash
php artisan make:policy StudentPolicy --model=Student
```

Enforce in controllers:

```php
$this->authorize('update', $student);
```

### Admin middleware

Create a dedicated `EnsureUserIsAdmin` middleware for admin-only routes rather than scattering role checks through controllers:

```php
public function handle(Request $request, Closure $next): Response
{
    abort_unless($request->user()?->is_admin, 403);
    return $next($request);
}
```

---

## 11. Database Security

### Prepared statements (always active)

Laravel's Eloquent ORM and the `DB` query builder always use PDO prepared statements with bound parameters — SQL injection is prevented by default. Never interpolate raw user input into query strings.

**Never do this:**

```php
// DANGEROUS — SQL injection
DB::select("SELECT * FROM students WHERE name = '{$request->name}'");
```

**Always do this:**

```php
DB::select("SELECT * FROM students WHERE name = ?", [$request->name]);
// or
Student::where('name', $request->name)->get();
```

### Column-injection prevention

`SendEmailVerificationChunkJob` demonstrates the whitelist pattern for dynamic column names:

```php
if (! in_array($idColumn, self::ALLOWED_ID_COLUMNS, true)) {
    throw new \InvalidArgumentException("Column '{$idColumn}' is not allowed.");
}
```

Always whitelist column names when they come from user input.

### Minimum database privileges

The `laravel` database user should have only the permissions it needs:

```sql
GRANT SELECT, INSERT, UPDATE, DELETE ON `laravel`.* TO 'laravel'@'localhost';
-- Do NOT grant DROP, CREATE, ALTER in production
```

Use a separate migration user with full DDL rights for deploys only.

---

## 12. Redis Security

### Authentication

Always set `requirepass` in `redis.conf` in any non-local environment:

```
requirepass <strong-random-password>
```

Set `REDIS_PASSWORD` in `.env` to match.

### Network binding

Bind Redis to `127.0.0.1` (loopback only) — never expose it to `0.0.0.0` on a public server:

```
bind 127.0.0.1 ::1
```

### TLS (production)

For Redis on a separate host, enable TLS:

```
tls-port 6380
tls-cert-file /etc/redis/tls/redis.crt
tls-key-file  /etc/redis/tls/redis.key
tls-ca-cert-file /etc/redis/tls/ca.crt
```

Update `config/database.php` to set `tls` on the Redis connection.

### Key namespace isolation

This application uses Redis for three separate purposes — queues, cache, and pub/sub. All three share the same Redis instance but use distinct key prefixes (`laravel_cache:`, `laravel_database_queues:`, etc.) set in `config/database.php`. Consider using separate Redis databases (`SELECT 0`/`1`/`2`) or a dedicated cluster for production workloads.

---

## 13. Queue & Job Security

### Signed job payloads

Laravel automatically signs serialised job payloads when `APP_KEY` is set. A tampered payload will fail deserialization and be rejected.

### Column whitelist in jobs

`SendEmailVerificationChunkJob` hard-codes the allowed values for `idColumn` and `emailColumn` in `ALLOWED_ID_COLUMNS` / `ALLOWED_EMAIL_COLUMNS`. This prevents column-injection attacks if a job payload is manipulated in the queue store.

### Job timeouts and retry limits

Every job class sets `$timeout` to prevent runaway workers:

```php
public int $timeout = 300;   // 5 minutes max
public int $tries   = 3;     // 3 attempts before moving to failed_jobs
```

Monitor failed jobs via Laravel Horizon or:

```bash
php artisan queue:failed
php artisan queue:retry all
```

### Horizon security

Restrict the Horizon dashboard (see [section 10](#10-access-control)). If Horizon is deployed publicly, add HTTP Basic Auth in Nginx as an additional layer:

```nginx
location /horizon {
    auth_basic           "Restricted";
    auth_basic_user_file /etc/nginx/.htpasswd;
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## 14. Logging & Auditing

### What is logged

| Event | Channel |
|---|---|
| All MySQL queries (dev) | `mysql_queries` (daily rotation, 14 days) |
| Queue job failures | `storage/logs/laravel.log` |
| Application errors | `storage/logs/laravel.log` |
| Worker output | `storage/logs/worker.log` (via Supervisor) |

### What must never be logged

- Passwords (hashed or plain text)
- Full payment card numbers (PCI DSS)
- Bearer tokens or OAuth secrets
- Full S3 presigned URLs (contain credentials in query string)

### Sensitive query redaction

The `AppServiceProvider` MySQL query logger already filters timestamps and booleans. Extend it to redact passwords from binding arrays if any query ever touches the `password` column.

### Audit trail (recommended)

For compliance or security-sensitive operations (role changes, data deletion, admin actions), add an audit log table:

```bash
composer require owen-it/laravel-auditing
php artisan vendor:publish --provider "OwenIt\Auditing\AuditingServiceProvider" --tag="config"
php artisan migrate
```

Add `\OwenIt\Auditing\Contracts\Auditable` and the `Auditable` trait to any model you want to track.

---

## 15. Dependency Security

### Composer audit

Run before every deployment to check for known vulnerabilities:

```bash
composer audit
```

### npm audit

```bash
npm audit
npm audit fix   # auto-fix where safe
```

### Automated scanning in CI

Add a security scan step to `.github/workflows/ci.yml`:

```yaml
- name: Composer audit
  run: composer audit

- name: npm audit
  run: npm audit --audit-level=high
```

### Keeping dependencies up-to-date

```bash
composer outdated --direct   # show outdated direct dependencies
composer update              # update within composer.json constraints
```

Use [Dependabot](https://docs.github.com/en/code-security/dependabot) (free for public repos) to receive automatic PRs for outdated dependencies.

---

## Quick Security Checklist

Use this before every production deployment:

- [ ] `APP_DEBUG=false` in `.env`
- [ ] `APP_ENV=production` in `.env`
- [ ] `APP_KEY` set (32-byte random base64 value)
- [ ] Strong `DB_PASSWORD` (not `laravel`)
- [ ] Strong `REDIS_PASSWORD` set
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_SAME_SITE=lax`
- [ ] All `AWS_*` credentials set (or IAM role attached to instance)
- [ ] `FILESYSTEM_DISK=s3` (never store user uploads on ephemeral app servers)
- [ ] Nginx security headers added (`X-Frame-Options`, `X-Content-Type-Options`, HSTS, CSP)
- [ ] TLS certificate installed and HTTP → HTTPS redirect active
- [ ] Horizon dashboard restricted to admin users
- [ ] Queue worker running under a non-root OS user (`www-data`)
- [ ] `composer audit` passes with no high/critical issues
- [ ] `npm audit --audit-level=high` passes
- [ ] Redis bound to loopback only (`bind 127.0.0.1`)
- [ ] Firewall only exposes ports 80, 443, and 22 (to your IP only)
