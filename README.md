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
