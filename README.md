<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

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

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
