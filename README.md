<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

---

# Laravel 12 — Redis, Horizon, Sanctum, Passport, Repository Pattern, CoreUI React

A full-featured Laravel 12 starter that demonstrates:
- **Redis-backed queues** with bulk user generation and email verification
- **Laravel Horizon** for queue monitoring
- **Laravel Sanctum** for SPA/token authentication
- **Laravel Passport** for OAuth2 authentication
- **Repository Pattern** with a shared `BaseRepository` for clean data-access abstraction
- **CoreUI React SPA** served via **Vite** for the frontend
- **University Management System** sample CRUD demonstrating 5-entity many-to-many relationships via intersection tables

---

## Table of Contents

- [Requirements](#requirements)
- [Setup: Local PC](#setup-local-pc)
  - [Linux (Ubuntu / Debian)](#linux-ubuntu--debian)
  - [macOS](#macos)
  - [Windows (WSL 2)](#windows-wsl-2)
- [Setup: GitHub Codespaces](#setup-github-codespaces)
- [Setup: AWS EC2](#setup-aws-ec2)
- [Setup: DigitalOcean Droplet](#setup-digitalocean-droplet)
- [Environment Variables Reference](#environment-variables-reference)
- [Running the Application](#running-the-application)
- [Feature Docs](#feature-docs)

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.2 or 8.3 |
| Composer | 2.x |
| Node.js | 20 or 22 |
| npm | 10+ |
| MySQL / MariaDB | 8.0+ / 10.6+ |
| Redis | 6.0+ |

---

## Setup: Local PC

### Linux (Ubuntu / Debian)

#### 1. Install system dependencies

```bash
sudo apt update && sudo apt upgrade -y

# PHP 8.3 + extensions
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-xml \
  php8.3-mbstring php8.3-curl php8.3-zip php8.3-bcmath php8.3-tokenizer \
  php8.3-pdo

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 22 (via NodeSource)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# MySQL
sudo apt install -y default-mysql-server default-mysql-client
sudo service mysql start

# Redis
sudo apt install -y redis-server
sudo service redis-server start
```

#### 2. Create the database

```bash
sudo mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS `laravel` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'laravel'@'localhost' IDENTIFIED BY 'laravel';
GRANT ALL PRIVILEGES ON `laravel`.* TO 'laravel'@'localhost';
FLUSH PRIVILEGES;
SQL
```

#### 3. Clone and configure the project

```bash
git clone https://github.com/farhankarim/laravel12-redis.git
cd laravel12-redis

cp .env.example .env
# Edit .env if your database credentials differ from the defaults
```

#### 4. Install dependencies and run migrations

```bash
composer install
php artisan key:generate
php artisan migrate

npm install
npm run build
```

#### 5. Start the development servers

```bash
composer run dev
```

This starts Laravel (`php artisan serve`), the Vite dev-server, and the queue worker concurrently. Open [http://localhost:5173](http://localhost:5173) in your browser.

---

### macOS

#### 1. Install system dependencies

```bash
# Homebrew (if not already installed)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# PHP 8.3
brew install php@8.3
echo 'export PATH="/opt/homebrew/opt/php@8.3/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 22
brew install node@22
echo 'export PATH="/opt/homebrew/opt/node@22/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc

# MySQL
brew install mysql
brew services start mysql

# Redis
brew install redis
brew services start redis
```

#### 2. Create the database

```bash
mysql -u root <<'SQL'
CREATE DATABASE IF NOT EXISTS `laravel` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'laravel'@'localhost' IDENTIFIED BY 'laravel';
GRANT ALL PRIVILEGES ON `laravel`.* TO 'laravel'@'localhost';
FLUSH PRIVILEGES;
SQL
```

#### 3. Clone and configure the project

```bash
git clone https://github.com/farhankarim/laravel12-redis.git
cd laravel12-redis

cp .env.example .env
```

#### 4. Install dependencies and run migrations

```bash
composer install
php artisan key:generate
php artisan migrate

npm install
npm run build
```

#### 5. Start the development servers

```bash
composer run dev
```

Open [http://localhost:5173](http://localhost:5173) in your browser.

---

### Windows (WSL 2)

#### 1. Enable WSL 2 and install Ubuntu

Open PowerShell as Administrator:

```powershell
wsl --install
# Restart when prompted, then open the Ubuntu app from the Start menu
```

#### 2. Follow the Linux (Ubuntu) instructions above

All subsequent steps are identical to the [Linux section](#linux-ubuntu--debian). Run every command inside the Ubuntu WSL terminal.

> **Tip:** Open your project in VS Code with `code .` from the WSL terminal — VS Code detects WSL automatically.

---

## Setup: GitHub Codespaces

The repository ships with a fully automated `.devcontainer` configuration. Everything — PHP, Node, MySQL, Redis — is installed and configured automatically.

#### 1. Open in Codespaces

Click **Code → Codespaces → Create codespace on main** (or your branch) on the GitHub repository page.

GitHub will:
- Provision a container based on `mcr.microsoft.com/devcontainers/php:1-8.3-bookworm`
- Install Node 22, MySQL, and Redis via `post-create.sh`
- Create the `laravel` database, user, and password
- Copy `.env.example` to `.env`
- Set `APP_URL` to your Codespace's forwarded URL
- Run `php artisan key:generate` and `php artisan migrate`

#### 2. Install project dependencies

Open the integrated terminal and run:

```bash
composer install
npm install
```

#### 3. Start the development servers

```bash
composer run dev
```

Vite starts on port **5173**. GitHub Codespaces automatically forwards this port. Click the **Open in Browser** notification or find the forwarded URL in the **Ports** tab.

> The app is accessed through the Vite proxy (port 5173), which forwards Laravel API requests to `php artisan serve` on port 8000. All generated URLs — redirects, signed mail links — are automatically rewritten to the Codespaces hostname.

#### Ports forwarded by the devcontainer

| Port | Service |
|---|---|
| 5173 | Vite dev-server (main entry point) |
| 8000 | Laravel (accessed via Vite proxy) |
| 3306 | MySQL |

---

## Setup: AWS EC2

The steps below target an **Ubuntu 24.04 LTS** instance. A `t3.small` (2 vCPU, 2 GB RAM) or larger is recommended for development; use `t3.medium` or larger for production workloads.

#### 1. Launch an EC2 instance

1. Go to **EC2 → Launch Instance** in the AWS console.
2. Choose **Ubuntu Server 24.04 LTS (HVM), SSD Volume Type**.
3. Select instance type (e.g., `t3.small`).
4. Under **Key pair**, create or select an existing key pair (you'll need the `.pem` file to SSH in).
5. Under **Network settings → Security group**, allow inbound traffic on:
   - **SSH** (port 22) — your IP only
   - **HTTP** (port 80) — `0.0.0.0/0`
   - **HTTPS** (port 443) — `0.0.0.0/0`
   - **Custom TCP 8000** — your IP (for direct Artisan serve, dev only)
6. Click **Launch Instance**.

#### 2. SSH into the instance

```bash
chmod 400 your-key.pem
ssh -i your-key.pem ubuntu@<EC2-PUBLIC-IP>
```

#### 3. Install system dependencies

```bash
sudo apt update && sudo apt upgrade -y

# PHP 8.3
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-xml \
  php8.3-mbstring php8.3-curl php8.3-zip php8.3-bcmath php8.3-tokenizer

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 22
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# MySQL
sudo apt install -y mysql-server
sudo systemctl enable --now mysql

# Redis
sudo apt install -y redis-server
sudo systemctl enable --now redis-server

# Nginx (optional, for production)
sudo apt install -y nginx
sudo systemctl enable --now nginx
```

#### 4. Secure MySQL and create the database

```bash
sudo mysql_secure_installation   # follow prompts

sudo mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS `laravel` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'laravel'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON `laravel`.* TO 'laravel'@'localhost';
FLUSH PRIVILEGES;
SQL
```

#### 5. Deploy the application

```bash
cd /var/www
sudo git clone https://github.com/farhankarim/laravel12-redis.git
sudo chown -R $USER:www-data laravel12-redis
cd laravel12-redis

cp .env.example .env
# Edit .env — update DB_PASSWORD and APP_URL (your server's IP or domain)
nano .env
```

Key `.env` values to update:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=http://<YOUR-EC2-PUBLIC-IP>

DB_HOST=127.0.0.1
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=StrongPassword123!

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null

TRUSTED_PROXIES=*
```

```bash
composer install --optimize-autoloader --no-dev
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

npm install
npm run build
```

#### 6. Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/laravel
```

Paste the following (replace `<YOUR-EC2-PUBLIC-IP>` with your IP or domain):

```nginx
server {
    listen 80;
    server_name <YOUR-EC2-PUBLIC-IP>;
    root /var/www/laravel12-redis/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Fix storage permissions
sudo chown -R www-data:www-data /var/www/laravel12-redis/storage
sudo chown -R www-data:www-data /var/www/laravel12-redis/bootstrap/cache
sudo chmod -R 775 /var/www/laravel12-redis/storage
```

#### 7. Run the queue worker (production)

Use a process manager to keep the worker alive:

```bash
sudo apt install -y supervisor

sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/laravel12-redis/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/laravel12-redis/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

## Setup: DigitalOcean Droplet

#### 1. Create a Droplet

1. Log in to [DigitalOcean](https://cloud.digitalocean.com).
2. Click **Create → Droplets**.
3. Choose **Ubuntu 24.04 (LTS) x64**.
4. Select a plan — **Basic / Regular** with **2 GB RAM / 1 vCPU** ($12/mo) is sufficient for development; use **2 vCPU / 4 GB** for production.
5. Add your **SSH key** (or set a root password).
6. Click **Create Droplet**.

#### 2. Configure firewall (recommended)

In the DigitalOcean dashboard go to **Networking → Firewalls → Create Firewall**:

| Type | Protocol | Port | Source |
|---|---|---|---|
| SSH | TCP | 22 | Your IP |
| HTTP | TCP | 80 | All IPv4/IPv6 |
| HTTPS | TCP | 443 | All IPv4/IPv6 |

Assign the firewall to your Droplet.

#### 3. SSH into the Droplet

```bash
ssh root@<DROPLET-IP>
```

#### 4. Install system dependencies

Same commands as the [AWS EC2 section](#3-install-system-dependencies-1). Run all the same `apt install` commands for PHP 8.3, Composer, Node 22, MySQL, Redis, and Nginx.

#### 5. Create a non-root user (recommended)

```bash
adduser deploy
usermod -aG sudo deploy
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy
```

Then SSH back in as `deploy`:

```bash
ssh deploy@<DROPLET-IP>
```

#### 6. Create the database

```bash
sudo mysql_secure_installation

sudo mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS `laravel` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'laravel'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON `laravel`.* TO 'laravel'@'localhost';
FLUSH PRIVILEGES;
SQL
```

#### 7. Deploy the application

```bash
cd /var/www
sudo git clone https://github.com/farhankarim/laravel12-redis.git
sudo chown -R deploy:www-data laravel12-redis
cd laravel12-redis

cp .env.example .env
nano .env
```

Set these values in `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=http://<DROPLET-IP>

DB_HOST=127.0.0.1
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=StrongPassword123!

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null

TRUSTED_PROXIES=*
```

```bash
composer install --optimize-autoloader --no-dev
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

npm install
npm run build
```

#### 8. Configure Nginx

Identical to the [AWS Nginx config](#6-configure-nginx) — replace `<YOUR-EC2-PUBLIC-IP>` with your Droplet IP.

```bash
sudo chown -R www-data:www-data /var/www/laravel12-redis/storage
sudo chown -R www-data:www-data /var/www/laravel12-redis/bootstrap/cache
sudo chmod -R 775 /var/www/laravel12-redis/storage
```

#### 9. Run the queue worker

Identical to the [AWS Supervisor config](#7-run-the-queue-worker-production). Use the same `supervisor` setup.

#### 10. (Optional) Add a domain and SSL

Point your domain's A record to the Droplet IP, then:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

Update `APP_URL` in `.env` and re-run `php artisan config:cache`.

---

## Environment Variables Reference

All settings live in `.env`. Copy `.env.example` to get started:

```bash
cp .env.example .env
```

| Variable | Default | Description |
|---|---|---|
| `APP_ENV` | `local` | `local`, `staging`, or `production` |
| `APP_DEBUG` | `true` | Set `false` in production |
| `APP_URL` | `http://localhost` | Full URL of the app (used in emails, redirects) |
| `DB_HOST` | `127.0.0.1` | MySQL host |
| `DB_PORT` | `3306` | MySQL port |
| `DB_DATABASE` | `laravel` | Database name |
| `DB_USERNAME` | `laravel` | Database user |
| `DB_PASSWORD` | `laravel` | Database password |
| `REDIS_CLIENT` | `predis` | `predis` (no extension required) |
| `REDIS_HOST` | `127.0.0.1` | Redis host |
| `REDIS_PASSWORD` | `null` | Redis password (set in production) |
| `REDIS_PORT` | `6379` | Redis port |
| `QUEUE_CONNECTION` | `redis` | Queue driver |
| `CACHE_STORE` | `redis` | Cache driver |
| `MAIL_MAILER` | `log` | Mail driver (`log`, `smtp`, etc.) |
| `TRUSTED_PROXIES` | `127.0.0.1` | Comma-separated proxy IPs, or `*` behind a load-balancer |

---

## Running the Application

### Development (all platforms)

```bash
# Starts Laravel, Vite, and the queue worker together
composer run dev
```

Open [http://localhost:5173](http://localhost:5173) for the full-stack dev experience (Vite proxies API requests to Laravel on port 8000).

Individual commands:

```bash
php artisan serve            # Laravel only
npm run dev                  # Vite only
php artisan queue:listen     # Queue worker only
php artisan horizon          # Horizon dashboard at /horizon
php artisan pail             # Real-time log tail
```

### Production

Ensure Nginx, PHP-FPM, and Supervisor (queue workers) are running:

```bash
sudo systemctl status nginx php8.3-fpm
sudo supervisorctl status laravel-worker:*
```

After deploying new code:

```bash
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
sudo supervisorctl restart laravel-worker:*
```

---

## Feature Docs

## Codespaces: MySQL

This project includes a `.devcontainer` setup that installs a local MySQL-compatible server in Codespaces during post-create.

1. Rebuild the Codespace container so the new `.devcontainer` configuration is applied.
2. During post-create, the database server is installed (if needed), started, and the following are created:
- Database: `laravel`
- User: `laravel`
- Password: `laravel`
3. Laravel is configured to use MySQL by default via `.env` / `.env.example`.

Useful command:

```bash
php artisan migrate
```

## Codespaces: Redis + queue generation

Redis is installed in post-create and started in post-start.

- Default queue connection is Redis (`QUEUE_CONNECTION=redis`).
- Redis client is Predis (`REDIS_CLIENT=predis`).

Generate 1,000,000 users through Redis queue jobs:

```bash
./scripts/generate-million-users.sh
```

Optional overrides:

```bash
TOTAL_USERS=1000000 CHUNK_SIZE=1000 WORKERS=6 QUEUE_NAME=user-imports QUEUE_CONNECTION=redis ./scripts/generate-million-users.sh
```

## Email verification batch + free mail testing

Recommended free mail testing service: **Mailtrap Email Testing** (free tier available).

Set these env values (from Mailtrap SMTP credentials) in `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Dispatch a queued batch for verification emails (for generated users):

```bash
php artisan users:queue-email-verifications --connection=redis --queue=email-verifications --id-column=user_id --email-column=email_address
```

Or run the helper script with parallel workers:

```bash
WORKERS=6 ID_COLUMN=user_id EMAIL_COLUMN=email_address ./scripts/queue-email-verifications.sh
```

Safe dry run on a small sample:

```bash
php artisan users:queue-email-verifications --connection=redis --queue=email-verifications --id-column=user_id --email-column=email_address --limit=20
```

## Livewire Redis dashboards

Livewire is installed and two dashboards are available:

- Queue summary: `/dashboard/queue`
- Users summary: `/dashboard/users`

The dashboards read/write cached summary snapshots using Redis keys:

- `dashboard:queue_summary`
- `dashboard:users_summary`

Pub/Sub channels:

- Refresh requests: `dashboard.summary.refresh`
- Update notifications: `dashboard.summary.updated`

Run the Redis pub/sub listener so refresh messages trigger summary rebuilds:

```bash
php artisan dashboard:redis-listen
```

Step-by-step implementation guide:

- [Livewire + Redis dashboard tutorial](docs/livewire-redis-dashboard-step-by-step.md)

---

## Laravel Horizon

Horizon provides a beautiful dashboard for monitoring Redis-backed queues.

### Setup

Horizon was installed via:

```bash
composer require laravel/horizon
php artisan horizon:install
```

This publishes `config/horizon.php` and registers `App\Providers\HorizonServiceProvider`.

### Run Horizon

```bash
php artisan horizon
```

### Access the Dashboard

Visit `/horizon` in your browser. In production, gate access in `App\Providers\HorizonServiceProvider::gate()`.

### Configuration (`config/horizon.php`)

- Adjust `environments.production.supervisor-1.maxProcesses` to match your server capacity.
- Use `balance: 'auto'` for automatic queue worker scaling.

---

## Laravel Sanctum

Sanctum provides lightweight token authentication for SPAs and simple API tokens.

### Setup

Sanctum was installed via:

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### Issuing Tokens

```php
$token = $user->createToken('my-app-token')->plainTextToken;
```

### Protecting Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $request) => $request->user());
});
```

### SPA Cookie Authentication

Add `\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class` to your `api` middleware group in `bootstrap/app.php`.

---

## Laravel Passport

Passport provides a full OAuth2 server implementation.

### Setup

Passport was installed via:

```bash
composer require laravel/passport
php artisan migrate
php artisan passport:install
```

### Configure `User` model

```php
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

### Configure `config/auth.php`

```php
'guards' => [
    'api' => [
        'driver'   => 'passport',
        'provider' => 'users',
    ],
],
```

### Issue Tokens

Use the `/oauth/token` endpoint with `grant_type=password` or `grant_type=client_credentials`.

---

## Repository Pattern

All data access is abstracted through the Repository Pattern. This decouples controllers from Eloquent, making the codebase testable and easy to swap data sources.

### Architecture

```
app/
  Repositories/
    Contracts/
      RepositoryInterface.php          ← Base interface (all repos implement this)
      StudentRepositoryInterface.php   ← Domain-specific additions
      CourseRepositoryInterface.php
      InstructorRepositoryInterface.php
      ClassroomRepositoryInterface.php
      DepartmentRepositoryInterface.php
    BaseRepository.php                 ← Abstract class: all(), find(), create(), update(), delete()
    StudentRepository.php              ← Concrete implementation
    CourseRepository.php
    InstructorRepository.php
    ClassroomRepository.php
    DepartmentRepository.php
    UserRepository.php
  Providers/
    RepositoryServiceProvider.php      ← Binds interfaces → concrete classes
```

`RepositoryServiceProvider` is registered in `bootstrap/providers.php`.

### `BaseRepository` Methods

| Method | Description |
|--------|-------------|
| `all(columns, relations)` | Fetch all records with optional eager loading |
| `find(id, columns, relations, appends)` | Find by primary key |
| `findByField(field, value, columns, relations)` | Where clause shortcut |
| `create(data)` | Mass-assign and persist |
| `update(id, data)` | Find and update |
| `delete(id)` | Find and delete |

### Adding a New Entity with Repository Pattern

Follow these steps any time you add a new model to the project:

#### Step 1 — Create the migration and model

```bash
php artisan make:model Widget -m
```

#### Step 2 — Create the contract interface

`app/Repositories/Contracts/WidgetRepositoryInterface.php`:

```php
<?php
namespace App\Repositories\Contracts;

interface WidgetRepositoryInterface extends RepositoryInterface {}
```

Add domain-specific methods here if needed (e.g. `findByColor(string $color): Collection`).

#### Step 3 — Create the concrete repository

`app/Repositories/WidgetRepository.php`:

```php
<?php
namespace App\Repositories;

use App\Models\Widget;
use App\Repositories\Contracts\WidgetRepositoryInterface;

class WidgetRepository extends BaseRepository implements WidgetRepositoryInterface
{
    protected function model(): string
    {
        return Widget::class;
    }

    // Add any custom methods here
}
```

#### Step 4 — Register the binding

In `app/Providers/RepositoryServiceProvider.php`, add to `register()`:

```php
$this->app->bind(
    \App\Repositories\Contracts\WidgetRepositoryInterface::class,
    \App\Repositories\WidgetRepository::class
);
```

#### Step 5 — Inject the repository in your controller

```php
<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\WidgetRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function __construct(protected WidgetRepositoryInterface $widgets) {}

    public function index(): JsonResponse
    {
        return response()->json($this->widgets->all());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        return response()->json($this->widgets->create($data), 201);
    }
}
```

#### Step 6 — Register API routes

In `routes/api.php`:

```php
Route::apiResource('widgets', \App\Http\Controllers\Api\WidgetController::class);
```

---

## University Management System

A sample CRUD demonstrating **5 main entities** linked by **4 intersection tables** — the classic many-to-many web for a University.

### Entities

| Entity | Table | Fields |
|--------|-------|--------|
| Student | `students` | `id`, `name`, `email` |
| Course | `courses` | `id`, `course_code`, `title` |
| Instructor | `instructors` | `id`, `name`, `specialization` |
| Classroom | `classrooms` | `id`, `room_number`, `building` |
| Department | `departments` | `id`, `dept_name` |

### Intersection Tables

| Intersection | Table | Links |
|---|---|---|
| Enrollment | `enrollments` | Student ↔ Course (+ `semester`, `grade`) |
| Course Assignment | `course_assignments` | Instructor ↔ Course |
| Department Faculty | `department_faculty` | Department ↔ Instructor |
| Schedule | `course_schedules` | Course ↔ Classroom (+ `day_of_week`, `start_time`) |

### ERD (text)

```
students ──────── enrollments ──────── courses
                                           │
                               course_assignments
                                           │
departments ── department_faculty ── instructors
                                           
courses ─────── course_schedules ──── classrooms
```

### Run Migrations

```bash
php artisan migrate
```

### API Endpoints (`/api/v1/`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/students` | List all students |
| POST | `/students` | Create a student |
| GET | `/students/{id}` | Get student with all relations |
| PUT/PATCH | `/students/{id}` | Update student |
| DELETE | `/students/{id}` | Delete student |
| POST | `/students/{id}/enroll` | Enroll in a course (`course_id`, `semester`) |
| PATCH | `/students/{id}/grade` | Update grade (`course_id`, `grade`) |
| GET | `/students/{id}/report` | 5-entity master report |
| GET/POST/PUT/DELETE | `/courses/{id}` | Full CRUD |
| GET/POST/PUT/DELETE | `/instructors/{id}` | Full CRUD |
| GET/POST/PUT/DELETE | `/classrooms/{id}` | Full CRUD |
| GET/POST/PUT/DELETE | `/departments/{id}` | Full CRUD |

### Master Report (5-way join)

`GET /api/v1/students/{id}/report` runs:

```sql
SELECT
    s.name       AS student,
    co.title     AS course,
    i.name       AS instructor,
    cl.room_number AS room,
    d.dept_name  AS department
FROM students s
JOIN enrollments e         ON s.id = e.student_id
JOIN courses co            ON e.course_id = co.id
JOIN course_assignments ca ON co.id = ca.course_id
JOIN instructors i         ON ca.instructor_id = i.id
JOIN department_faculty df ON i.id = df.instructor_id
JOIN departments d         ON df.department_id = d.id
JOIN course_schedules cs   ON co.id = cs.course_id
JOIN classrooms cl         ON cs.classroom_id = cl.id
WHERE s.id = ?
```

### Eloquent Relationships Quick Reference

```php
// Get all courses a student is enrolled in, with instructor and department
$student->courses()->with('instructors.departments')->get();

// Get all students in a course
$course->students;

// Get all courses taught by an instructor
$instructor->courses;

// Get all instructors in a department
$department->instructors;

// Get all classrooms where a course is scheduled
$course->classrooms()->withPivot('day_of_week', 'start_time')->get();
```

---

## CoreUI React Frontend

A React SPA is available at `/university`, built with [CoreUI React](https://coreui.io/react/) and served via Vite.

### File Structure

```
resources/js/university/
  main.jsx                     ← App entry, BrowserRouter, sidebar layout
  components/
    CrudPage.jsx               ← Generic CRUD table+form component (reusable)
  pages/
    StudentsPage.jsx
    CoursesPage.jsx
    InstructorsPage.jsx
    ClassroomsPage.jsx
    DepartmentsPage.jsx
    ReportPage.jsx             ← 5-entity master report UI
```

The `CrudPage` component accepts `title`, `apiPath`, `fields`, and `displayColumns` props — just configure it for any entity.

### Run Dev Server

```bash
npm run dev
```

### Build for Production

```bash
npm run build
```

### Access the SPA

Navigate to `/university` in your browser.

### Adding a New Page for a New Entity

1. Create `resources/js/university/pages/WidgetsPage.jsx`:

```jsx
import React from 'react';
import CrudPage from '../components/CrudPage.jsx';

export default function WidgetsPage() {
  return (
    <CrudPage
      title="Widgets"
      apiPath="/api/v1/widgets"
      fields={[
        { key: 'name', label: 'Name' },
        { key: 'color', label: 'Color' },
      ]}
      displayColumns={[
        { key: 'name', label: 'Name' },
        { key: 'color', label: 'Color' },
      ]}
    />
  );
}
```

2. Import and add a route in `resources/js/university/main.jsx`:

```jsx
import WidgetsPage from './pages/WidgetsPage.jsx';

// Inside <Routes>:
<Route path="/widgets" element={<WidgetsPage />} />
```

3. Add a nav item in the `<CSidebarNav>` section:

```jsx
<CNavItem href="/university/widgets">Widgets</CNavItem>
```

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
