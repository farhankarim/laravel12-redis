# Building a University Management System with Laravel 12, Redis, Livewire & React

> A comprehensive, book-style guide to recreating this application from scratch — covering the full stack: REST API, Repository Pattern, Redis Queues, Pub/Sub Dashboards, Horizon, Passport, Livewire, and a React SPA.

---

## Table of Contents

1. [Introduction & Architecture Overview](#1-introduction--architecture-overview)
2. [Prerequisites & Tool Requirements](#2-prerequisites--tool-requirements)
3. [Creating the Laravel 12 Project](#3-creating-the-laravel-12-project)
4. [Environment Configuration](#4-environment-configuration)
5. [Database Design & Migrations](#5-database-design--migrations)
6. [Eloquent Models & Relationships](#6-eloquent-models--relationships)
7. [Repository Pattern](#7-repository-pattern)
8. [API Authentication with Laravel Sanctum](#8-api-authentication-with-laravel-sanctum)
9. [Building the REST API Controllers](#9-building-the-rest-api-controllers)
10. [Defining API Routes](#10-defining-api-routes)
11. [Service Providers & Dependency Injection](#11-service-providers--dependency-injection)
12. [Redis as Queue Driver & Cache Store](#12-redis-as-queue-driver--cache-store)
13. [Queue Jobs for Bulk Operations](#13-queue-jobs-for-bulk-operations)
14. [Artisan Commands for Queue Dispatch](#14-artisan-commands-for-queue-dispatch)
15. [Email Verification Notification](#15-email-verification-notification)
16. [Laravel Horizon — Queue Monitoring](#16-laravel-horizon--queue-monitoring)
17. [Redis Pub/Sub Dashboard Service](#17-redis-pubsub-dashboard-service)
18. [Livewire Real-Time Dashboards](#18-livewire-real-time-dashboards)
19. [Web Routes & Livewire Wiring](#19-web-routes--livewire-wiring)
20. [React SPA Frontend (Vite + CoreUI)](#20-react-spa-frontend-vite--coreui)
21. [SEO — Sitemap, Meta Tags & Structured Data](#21-seo--sitemap-meta-tags--structured-data)
22. [Database Seeding](#22-database-seeding)
23. [Testing](#23-testing)
24. [Running the Full Stack](#24-running-the-full-stack)
25. [Architecture Diagram & Summary](#25-architecture-diagram--summary)

---

## 1. Introduction & Architecture Overview

This application is a **University Management System** that demonstrates advanced real-world patterns using a modern PHP stack. It manages students, courses, instructors, classrooms, and departments through a fully authenticated REST API, while also providing real-time operational dashboards powered by Redis Pub/Sub and Livewire.

### What You Will Build

| Layer | Technology |
|---|---|
| Framework | Laravel 12 (PHP 8.2+) |
| Database | MySQL with Eloquent ORM |
| Cache & Queue | Redis via `predis/predis` |
| Queue Monitoring | Laravel Horizon |
| API Auth | Laravel Sanctum (token-based) |
| OAuth2 | Laravel Passport |
| Real-Time UI | Livewire 4 |
| Frontend SPA | React 19 + CoreUI + Vite 7 |
| CSS | Tailwind CSS 4 |

### Key Architectural Decisions

**Repository Pattern**: Every Eloquent model has a corresponding repository interface and concrete class. Controllers never interact with models directly — they depend on interfaces injected by the service container. This decouples your database layer from your HTTP layer and makes unit testing straightforward.

**Redis for Everything**: Redis serves three roles simultaneously: queue backend (fast, reliable job dispatch), cache store (TTL-based data storage), and pub/sub message broker (real-time event fan-out to dashboard listeners).

**Chunked Queue Jobs**: Bulk operations (generating a million users, sending verification emails) are decomposed into thousands of small, independently-retriable queue jobs. This prevents timeouts, allows horizontal scaling across workers, and provides granular failure recovery.

**Livewire Polling + Pub/Sub**: Dashboard components poll Redis for cached summaries every 10 seconds. A long-running `dashboard:redis-listen` command subscribes to a Redis channel and pushes refreshed data back to Redis whenever a `refresh` message arrives from any Livewire component.

---

## 2. Prerequisites & Tool Requirements

Before writing a line of code, make sure the following are installed on your machine.

### System Requirements

```bash
# PHP 8.2 or higher with extensions
php --version          # 8.2+
php -m | grep -E "redis|pdo_mysql|mbstring|xml|curl|zip|bcmath"

# Composer (PHP package manager)
composer --version     # 2.x

# Node.js and npm
node --version         # 18+
npm --version          # 9+

# MySQL (or MariaDB)
mysql --version

# Redis server
redis-server --version  # 7+
redis-cli ping          # should respond PONG
```

### Installing Redis on Ubuntu / Debian

```bash
sudo apt update
sudo apt install redis-server -y
sudo systemctl enable redis-server
sudo systemctl start redis-server
redis-cli ping   # PONG
```

### Installing Redis on macOS (Homebrew)

```bash
brew install redis
brew services start redis
redis-cli ping   # PONG
```

---

## 3. Creating the Laravel 12 Project

### Step 1 — Scaffold the Project

```bash
composer create-project laravel/laravel university-app
cd university-app
```

> Laravel 12 ships with PHP 8.2 requirements, anonymous migration classes, and improved Eloquent performance.

### Step 2 — Install Core Dependencies

```bash
# Redis client for PHP
composer require predis/predis

# Laravel Horizon (queue dashboard backed by Redis)
composer require laravel/horizon

# Laravel Passport (OAuth2 server)
composer require laravel/passport

# Laravel Sanctum (API token authentication)
composer require laravel/sanctum

# Livewire (server-side reactive components)
composer require livewire/livewire
```

### Step 3 — Install Frontend Dependencies

```bash
npm install

# CoreUI React component library
npm install @coreui/react @coreui/coreui @coreui/icons @coreui/icons-react

# React 19 + router + Helmet for SPA
npm install react react-dom react-router-dom react-helmet-async sweetalert2

# Vite plugins
npm install --save-dev @vitejs/plugin-react @tailwindcss/vite tailwindcss
```

---

## 4. Environment Configuration

Edit `.env` to configure your local services. Copy `.env.example` first:

```bash
cp .env.example .env
php artisan key:generate
```

### `.env` Key Settings

```dotenv
APP_NAME="University App"
APP_ENV=local
APP_KEY=           # generated by artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=university
DB_USERNAME=root
DB_PASSWORD=secret

# Redis (Predis client)
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Use Redis as queue driver — required for Horizon
QUEUE_CONNECTION=redis

# Use Redis as the default cache store
CACHE_STORE=redis

# Use database-backed sessions
SESSION_DRIVER=database

# Mail — use "log" driver for local development
MAIL_MAILER=log

# Comma-separated queue names to monitor in the dashboard
QUEUE_MONITORED=default,user-imports,email-verifications
```

### `config/database.php` — Redis Connection

Open `config/database.php` and confirm the Redis block uses the `REDIS_CLIENT=predis` setting:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),

    'default' => [
        'url'      => env('REDIS_URL'),
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port'     => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],

    'cache' => [
        'url'      => env('REDIS_URL'),
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port'     => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

> **Why Predis?** The `predis/predis` pure-PHP client works in any environment without a compiled C extension, which makes it ideal for containerized deployments and GitHub Codespaces. Switch to `phpredis` for raw performance in production.

---

## 5. Database Design & Migrations

The application has nine core tables (plus Laravel system tables). Create them in the following order, since they have foreign-key dependencies.

### Step 1 — Run Built-in Laravel Migrations

```bash
php artisan migrate
```

This creates: `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

### Step 2 — Students Table

```bash
php artisan make:migration create_students_table
```

```php
// database/migrations/2024_01_01_000001_create_students_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
```

### Step 3 — Departments Table

```bash
php artisan make:migration create_departments_table
```

```php
// database/migrations/2024_01_01_000002_create_departments_table.php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('dept_name')->unique();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('departments'); }
};
```

### Step 4 — Instructors Table

```bash
php artisan make:migration create_instructors_table
```

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('specialization')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('instructors'); }
};
```

### Step 5 — Classrooms Table

```bash
php artisan make:migration create_classrooms_table
```

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number');
            $table->string('building');
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('classrooms'); }
};
```

### Step 6 — Courses Table

```bash
php artisan make:migration create_courses_table
```

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code')->unique();
            $table->string('title');
            $table->unsignedTinyInteger('credit_hours')->default(3);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('courses'); }
};
```

### Step 7 — Enrollments Pivot Table

The `enrollments` table is the many-to-many join between students and courses, enriched with a `semester` and `grade`.

```bash
php artisan make:migration create_enrollments_table
```

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            // Composite primary key prevents duplicate enrollments
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('semester', 20);
            $table->char('grade', 2)->nullable();
            $table->primary(['student_id', 'course_id']);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('enrollments'); }
};
```

> **Design note**: The composite primary key `(student_id, course_id)` prevents a student from being enrolled in the same course twice via a database-level constraint, not just application logic.

### Step 8 — Course Assignments (Instructor ↔ Course)

Each course has exactly one instructor. A `UNIQUE` constraint on `course_id` enforces this rule at the database level.

```bash
php artisan make:migration create_course_assignments_table
```

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_assignments', function (Blueprint $table) {
            $table->foreignId('instructor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->primary(['instructor_id', 'course_id']);
            $table->unique('course_id', 'course_assignments_course_id_unique'); // one instructor per course
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('course_assignments'); }
};
```

### Step 9 — Department Faculty (Instructor ↔ Department)

```bash
php artisan make:migration create_department_faculty_table
```

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('department_faculty', function (Blueprint $table) {
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained()->cascadeOnDelete();
            $table->primary(['department_id', 'instructor_id']);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('department_faculty'); }
};
```

### Step 10 — Course Schedules (Course ↔ Classroom)

```bash
php artisan make:migration create_course_schedules_table
```

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_schedules', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('day_of_week', 10);
            $table->time('start_time');
            $table->primary(['course_id', 'classroom_id', 'day_of_week']);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('course_schedules'); }
};
```

### Step 11 — Run All Migrations

```bash
php artisan migrate
```

---

## 6. Eloquent Models & Relationships

Create a model for each table. Eloquent models declare `$fillable` (mass-assignment whitelist) and define relationships.

### Student Model

```bash
php artisan make:model Student
```

```php
// app/Models/Student.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email'];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot('semester', 'grade')
            ->withTimestamps();
    }
}
```

### Course Model

```php
// app/Models/Course.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['course_code', 'title', 'credit_hours'];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollments')
            ->withPivot('semester', 'grade')
            ->withTimestamps();
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class, 'course_assignments')
            ->withTimestamps();
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'course_schedules')
            ->withPivot('day_of_week', 'start_time')
            ->withTimestamps();
    }
}
```

### Instructor Model

```php
// app/Models/Instructor.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Instructor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'specialization'];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_assignments')
            ->withTimestamps();
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_faculty')
            ->withTimestamps();
    }
}
```

### Department Model

```php
// app/Models/Department.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['dept_name'];

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class, 'department_faculty')
            ->withTimestamps();
    }
}
```

### Classroom Model

```php
// app/Models/Classroom.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = ['room_number', 'building'];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_schedules')
            ->withPivot('day_of_week', 'start_time')
            ->withTimestamps();
    }
}
```

### User Model

The `User` model uses the `HasApiTokens` trait from Sanctum to support token-based authentication.

```php
// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
```

---

## 7. Repository Pattern

The Repository Pattern decouples your data-access logic from your controllers. Each entity gets three files:

1. **Interface** — contract defining available data operations
2. **Concrete Repository** — implements the interface using Eloquent/DB
3. **BaseRepository** — shared CRUD inherited by all concrete repositories

### 7.1 The RepositoryInterface Contract

```bash
mkdir -p app/Repositories/Contracts
```

```php
// app/Repositories/Contracts/RepositoryInterface.php
namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    public function all(array $columns = ['*'], array $relations = []): Collection;
    public function find(int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model;
    public function findByField(string $field, mixed $value, array $columns = ['*'], array $relations = []): Collection;
    public function create(array $data): Model;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
```

### 7.2 BaseRepository — Shared CRUD Implementation

```php
// app/Repositories/BaseRepository.php
namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    public function __construct()
    {
        // Resolves the model class string returned by model() through the IoC container
        $this->model = app($this->model());
    }

    /**
     * Return the fully-qualified model class name.
     */
    abstract protected function model(): string;

    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    public function find(int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        $model = $this->model->select($columns)->with($relations)->find($id);

        if ($model && ! empty($appends)) {
            $model->append($appends);
        }

        return $model;
    }

    public function findByField(string $field, mixed $value, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->select($columns)->with($relations)->where($field, $value)->get();
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $record = $this->model->find($id);
        if (! $record) {
            return false;
        }
        return $record->update($data);
    }

    public function delete(int $id): bool
    {
        $record = $this->model->find($id);
        if (! $record) {
            return false;
        }
        return (bool) $record->delete();
    }
}
```

### 7.3 Concrete Repository Interfaces

Each entity-specific interface extends the base and can declare additional domain methods.

```php
// app/Repositories/Contracts/StudentRepositoryInterface.php
namespace App\Repositories\Contracts;

interface StudentRepositoryInterface extends RepositoryInterface
{
    public function enroll(int $studentId, int $courseId, string $semester): array;
    public function updateGrade(int $studentId, int $courseId, string $grade): bool;
    public function masterReport(?int $studentId = null, array $columns = [], bool $includeAllStudents = false): array;
}
```

```php
// app/Repositories/Contracts/CourseRepositoryInterface.php
namespace App\Repositories\Contracts;

interface CourseRepositoryInterface extends RepositoryInterface
{
    public function getAvailableStudents(int $courseId): array;
    public function getAssignedStudents(int $courseId): array;
    public function validateStudentAssignmentConflicts(int $courseId, array $studentIds, string $semester): array;
    public function bulkAssignStudents(int $courseId, array $studentIds, string $semester): array;
    public function revokeStudents(int $courseId, array $studentIds): int;
    public function getAvailableInstructors(int $courseId): array;
    public function getAssignedInstructors(int $courseId): array;
    public function bulkAssignInstructors(int $courseId, array $instructorIds): int;
    public function revokeInstructors(int $courseId, array $instructorIds): int;
}
```

Simple marker interfaces for entities with no extra domain methods:

```php
// app/Repositories/Contracts/InstructorRepositoryInterface.php
namespace App\Repositories\Contracts;
interface InstructorRepositoryInterface extends RepositoryInterface {}

// app/Repositories/Contracts/ClassroomRepositoryInterface.php
namespace App\Repositories\Contracts;
interface ClassroomRepositoryInterface extends RepositoryInterface {}

// app/Repositories/Contracts/DepartmentRepositoryInterface.php
namespace App\Repositories\Contracts;
interface DepartmentRepositoryInterface extends RepositoryInterface {}
```

### 7.4 Simple Concrete Repositories

```php
// app/Repositories/InstructorRepository.php
namespace App\Repositories;

use App\Models\Instructor;
use App\Repositories\Contracts\InstructorRepositoryInterface;

class InstructorRepository extends BaseRepository implements InstructorRepositoryInterface
{
    protected function model(): string { return Instructor::class; }
}
```

Create identical files for `ClassroomRepository` and `DepartmentRepository`, pointing to their respective models.

### 7.5 StudentRepository — Enrollment & Master Report

The `StudentRepository` contains the most complex domain logic: enrollment with schedule-conflict detection and a flexible multi-column master report builder using raw SQL joins.

```php
// app/Repositories/StudentRepository.php
namespace App\Repositories;

use App\Models\Student;
use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class StudentRepository extends BaseRepository implements StudentRepositoryInterface
{
    /**
     * Maps human-readable column aliases to their raw SQL table.column expressions.
     * Keeps report building safe from column injection.
     */
    private const MASTER_REPORT_SELECT_MAP = [
        'student_id'                => 's.id',
        'student_name'              => 's.name',
        'student_email'             => 's.email',
        'course_code'               => 'co.course_code',
        'course_title'              => 'co.title',
        'semester'                  => 'e.semester',
        'grade'                     => 'e.grade',
        'instructor_name'           => 'i.name',
        'instructor_specialization' => 'i.specialization',
        'classroom_room_number'     => 'cl.room_number',
        'classroom_building'        => 'cl.building',
        'schedule_day'              => 'cs.day_of_week',
        'schedule_start_time'       => 'cs.start_time',
        'department_name'           => 'd.dept_name',
    ];

    protected function model(): string { return Student::class; }

    /**
     * Enroll a student in a course, checking for schedule conflicts first.
     *
     * The conflict check looks for any other course the student is already
     * enrolled in during the same semester that occupies the same time slot
     * in the course_schedules table.
     *
     * @return array{enrolled: bool, conflict: bool}
     */
    public function enroll(int $studentId, int $courseId, string $semester): array
    {
        // 1. Fetch the schedule of the target course
        $targetSchedule = DB::table('course_schedules')
            ->where('course_id', $courseId)
            ->select('day_of_week', 'start_time')
            ->get();

        // 2. Only run conflict check if the course has a defined schedule
        if ($targetSchedule->isNotEmpty()) {
            $hasConflict = DB::table('enrollments as e')
                ->join('course_schedules as cs', 'e.course_id', '=', 'cs.course_id')
                ->where('e.student_id', $studentId)
                ->where('e.semester', $semester)
                ->where('e.course_id', '!=', $courseId)
                ->where(function ($query) use ($targetSchedule) {
                    foreach ($targetSchedule as $slot) {
                        $query->orWhere(function ($slotQuery) use ($slot) {
                            $slotQuery->where('cs.day_of_week', $slot->day_of_week)
                                      ->where('cs.start_time', $slot->start_time);
                        });
                    }
                })
                ->exists();

            if ($hasConflict) {
                return ['enrolled' => false, 'conflict' => true];
            }
        }

        // 3. Attempt insert, ignoring if the row already exists (idempotent)
        $inserted = DB::table('enrollments')->insertOrIgnore([
            'student_id' => $studentId,
            'course_id'  => $courseId,
            'semester'   => $semester,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['enrolled' => (bool) $inserted, 'conflict' => false];
    }

    public function updateGrade(int $studentId, int $courseId, string $grade): bool
    {
        return (bool) DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->update(['grade' => $grade, 'updated_at' => now()]);
    }

    /**
     * Build a flexible master report query joining all relevant tables.
     *
     * @param  array<string>  $columns  Subset of keys from MASTER_REPORT_SELECT_MAP
     */
    public function masterReport(?int $studentId = null, array $columns = [], bool $includeAllStudents = false): array
    {
        $selectedColumns = collect($columns)
            ->filter(fn ($col) => array_key_exists($col, self::MASTER_REPORT_SELECT_MAP))
            ->unique()
            ->values();

        // Fall back to a sensible default set if no valid columns were requested
        if ($selectedColumns->isEmpty()) {
            $selectedColumns = collect(['student_name', 'course_title', 'instructor_name',
                                        'classroom_room_number', 'department_name']);
        }

        $query = DB::table('students as s')
            ->join('enrollments as e', 's.id', '=', 'e.student_id')
            ->join('courses as co', 'e.course_id', '=', 'co.id')
            ->leftJoin('course_assignments as ca', 'co.id', '=', 'ca.course_id')
            ->leftJoin('instructors as i', 'ca.instructor_id', '=', 'i.id')
            ->leftJoin('department_faculty as df', 'i.id', '=', 'df.instructor_id')
            ->leftJoin('departments as d', 'df.department_id', '=', 'd.id')
            ->leftJoin('course_schedules as cs', 'co.id', '=', 'cs.course_id')
            ->leftJoin('classrooms as cl', 'cs.classroom_id', '=', 'cl.id');

        if (! $includeAllStudents && $studentId !== null) {
            $query->where('s.id', $studentId);
        }

        foreach ($selectedColumns as $column) {
            $query->addSelect(DB::raw(self::MASTER_REPORT_SELECT_MAP[$column] . ' as ' . $column));
        }

        return $query
            ->orderBy('s.id')
            ->orderBy('co.id')
            ->get()
            ->map(function ($row) use ($selectedColumns) {
                $normalized = [];
                foreach ($selectedColumns as $column) {
                    $normalized[$column] = $row->{$column} ?? null;
                }
                return $normalized;
            })
            ->all();
    }
}
```

### 7.6 CourseRepository — Bulk Enrollment with Conflict Validation

The `CourseRepository` allows bulk-assigning multiple students to a course at once, running the same schedule-conflict check against the entire batch in a single query pass.

```php
// app/Repositories/CourseRepository.php
namespace App\Repositories;

use App\Models\Course;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CourseRepository extends BaseRepository implements CourseRepositoryInterface
{
    protected function model(): string { return Course::class; }

    public function getAvailableStudents(int $courseId): array
    {
        return DB::table('students')
            ->whereNotIn('id', function ($q) use ($courseId) {
                $q->select('student_id')->from('enrollments')->where('course_id', $courseId);
            })
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function getAssignedStudents(int $courseId): array
    {
        return DB::table('students')
            ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->where('enrollments.course_id', $courseId)
            ->select('students.id', 'students.name', 'students.email')
            ->orderBy('students.name')
            ->get()
            ->toArray();
    }

    /**
     * For a list of candidate student IDs, identify which ones have a
     * schedule conflict in the given semester.
     */
    public function validateStudentAssignmentConflicts(int $courseId, array $studentIds, string $semester): array
    {
        $targetStudentIds = collect($studentIds)->map(fn ($id) => (int)$id)->unique()->values();

        $targetSchedule = DB::table('course_schedules')
            ->where('course_id', $courseId)
            ->select('day_of_week', 'start_time')
            ->get();

        $conflictStudentIds = collect();

        if ($targetSchedule->isNotEmpty()) {
            $conflictRows = DB::table('enrollments as e')
                ->join('course_schedules as cs', 'e.course_id', '=', 'cs.course_id')
                ->whereIn('e.student_id', $targetStudentIds->all())
                ->where('e.semester', $semester)
                ->where('e.course_id', '!=', $courseId)
                ->where(function ($query) use ($targetSchedule) {
                    foreach ($targetSchedule as $slot) {
                        $query->orWhere(function ($sq) use ($slot) {
                            $sq->where('cs.day_of_week', $slot->day_of_week)
                               ->where('cs.start_time', $slot->start_time);
                        });
                    }
                })
                ->select('e.student_id')
                ->distinct()
                ->get();

            $conflictStudentIds = $conflictRows->pluck('student_id')->map(fn ($id) => (int)$id)->values();
        }

        $assignableStudentIds = $targetStudentIds->reject(fn ($id) => $conflictStudentIds->contains($id))->values();

        $conflictStudents = $conflictStudentIds->isNotEmpty()
            ? DB::table('students')->whereIn('id', $conflictStudentIds->all())
                  ->select('id', 'name', 'email')->orderBy('name')->get()
                  ->map(fn ($s) => ['id' => (int)$s->id, 'name' => $s->name, 'email' => $s->email])
                  ->all()
            : [];

        return [
            'requested_count'       => $targetStudentIds->count(),
            'assignable_count'      => $assignableStudentIds->count(),
            'conflict_count'        => $conflictStudentIds->count(),
            'assignable_student_ids' => $assignableStudentIds->all(),
            'conflict_students'     => $conflictStudents,
        ];
    }

    public function bulkAssignStudents(int $courseId, array $studentIds, string $semester): array
    {
        $validation = $this->validateStudentAssignmentConflicts($courseId, $studentIds, $semester);
        $assignableStudentIds = collect($validation['assignable_student_ids'] ?? []);

        $assignedCount = 0;

        if ($assignableStudentIds->isNotEmpty()) {
            $records = $assignableStudentIds->map(fn ($sid) => [
                'student_id' => $sid,
                'course_id'  => $courseId,
                'semester'   => $semester,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            $assignedCount = DB::table('enrollments')->insertOrIgnore($records);
        }

        return [
            'assigned_count'   => (int) $assignedCount,
            'conflict_count'   => (int) ($validation['conflict_count'] ?? 0),
            'conflict_students' => $validation['conflict_students'] ?? [],
        ];
    }

    public function revokeStudents(int $courseId, array $studentIds): int
    {
        return DB::table('enrollments')
            ->where('course_id', $courseId)
            ->whereIn('student_id', $studentIds)
            ->delete();
    }

    public function getAvailableInstructors(int $courseId): array
    {
        $assignedId = DB::table('course_assignments')->where('course_id', $courseId)->value('instructor_id');

        return DB::table('instructors')
            ->when($assignedId, fn ($q) => $q->where('id', '!=', $assignedId))
            ->select('id', 'name', 'specialization')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function getAssignedInstructors(int $courseId): array
    {
        return DB::table('instructors')
            ->join('course_assignments', 'instructors.id', '=', 'course_assignments.instructor_id')
            ->where('course_assignments.course_id', $courseId)
            ->select('instructors.id', 'instructors.name', 'instructors.specialization')
            ->orderBy('instructors.name')
            ->get()
            ->toArray();
    }

    /**
     * Assign exactly one instructor to a course, replacing any existing assignment.
     * Uses updateOrInsert for atomicity.
     */
    public function bulkAssignInstructors(int $courseId, array $instructorIds): int
    {
        $instructorId = (int) ($instructorIds[0] ?? 0);
        if ($instructorId <= 0) return 0;

        $now = now();
        DB::table('course_assignments')->updateOrInsert(
            ['course_id' => $courseId],
            ['instructor_id' => $instructorId, 'created_at' => $now, 'updated_at' => $now]
        );

        return 1;
    }

    public function revokeInstructors(int $courseId, array $instructorIds): int
    {
        return DB::table('course_assignments')
            ->where('course_id', $courseId)
            ->whereIn('instructor_id', $instructorIds)
            ->delete();
    }
}
```

---

## 8. API Authentication with Laravel Sanctum

Sanctum provides a simple token-based API authentication system. Each user can create named personal access tokens.

### Install Sanctum Migrations

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### Auth Controller

```bash
php artisan make:controller Api/AuthController
```

```php
// app/Http/Controllers/Api/AuthController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user  = User::create($data);
        $token = $user->createToken('api')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($data)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        /** @var User $user */
        $user  = Auth::user();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
```

> **How it works**: `createToken('api')` generates a Sanctum personal access token. The plain-text version (returned once) is sent in the `Authorization: Bearer <token>` header with subsequent API requests. The `auth:sanctum` middleware validates the hashed token stored in the `personal_access_tokens` table.

---

## 9. Building the REST API Controllers

Each controller receives its repository through constructor injection and delegates all data operations to it.

### StudentController

```bash
php artisan make:controller Api/StudentController
```

```php
// app/Http/Controllers/Api/StudentController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    private const MASTER_REPORT_COLUMNS = [
        'student_id', 'student_name', 'student_email', 'course_code', 'course_title',
        'semester', 'grade', 'instructor_name', 'instructor_specialization',
        'classroom_room_number', 'classroom_building', 'schedule_day',
        'schedule_start_time', 'department_name',
    ];

    public function __construct(protected StudentRepositoryInterface $students) {}

    public function index(): JsonResponse
    {
        return response()->json($this->students->all(['*'], ['courses']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:students',
        ]);
        return response()->json($this->students->create($data), 201);
    }

    public function show(int $id): JsonResponse
    {
        $student = $this->students->find($id, ['*'], ['courses.instructors.departments', 'courses.classrooms']);
        return $student
            ? response()->json($student)
            : response()->json(['message' => 'Not found'], 404);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:students,email,' . $id,
        ]);
        return response()->json(['updated' => $this->students->update($id, $data)]);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json(['deleted' => $this->students->delete($id)]);
    }

    public function enroll(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'semester'  => 'required|string|max:20',
        ]);

        $result = $this->students->enroll($id, $data['course_id'], $data['semester']);

        if ($result['conflict']) {
            return response()->json([
                'message' => 'Enrollment blocked due to schedule overlap in the same semester.',
            ], 422);
        }

        return response()->json(['message' => 'Enrolled successfully']);
    }

    public function updateGrade(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'grade'     => 'required|string|max:2',
        ]);
        return response()->json(['updated' => $this->students->updateGrade($id, $data['course_id'], $data['grade'])]);
    }

    public function masterReport(int $id): JsonResponse
    {
        return response()->json($this->students->masterReport($id));
    }

    /**
     * Dynamic report builder — the client chooses which columns to include
     * and whether to scope the report to one student or all students.
     */
    public function masterReportBuilder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope'      => 'required|in:all,student',
            'student_id' => 'nullable|required_if:scope,student|integer|exists:students,id',
            'columns'    => 'required|array|min:1',
            'columns.*'  => 'required|string|in:' . implode(',', self::MASTER_REPORT_COLUMNS),
        ]);

        $scope     = $data['scope'];
        $studentId = $scope === 'student' ? (int) $data['student_id'] : null;
        $columns   = array_values(array_unique($data['columns']));

        $rows = $this->students->masterReport($studentId, $columns, $scope === 'all');

        return response()->json([
            'meta' => [
                'scope'      => $scope,
                'student_id' => $studentId,
                'columns'    => $columns,
                'total_rows' => count($rows),
            ],
            'rows' => $rows,
        ]);
    }
}
```

### CourseController

```bash
php artisan make:controller Api/CourseController
```

```php
// app/Http/Controllers/Api/CourseController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(protected CourseRepositoryInterface $courses) {}

    public function index(): JsonResponse
    {
        return response()->json($this->courses->all(['*'], ['instructors', 'classrooms']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'course_code'  => 'required|string|unique:courses',
            'title'        => 'required|string|max:255',
            'credit_hours' => 'required|integer|min:1|max:6',
        ]);
        return response()->json($this->courses->create($data), 201);
    }

    public function show(int $id): JsonResponse
    {
        $course = $this->courses->find($id, ['*'], ['students', 'instructors', 'classrooms']);
        return $course
            ? response()->json($course)
            : response()->json(['message' => 'Not found'], 404);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'course_code'  => 'sometimes|string|unique:courses,course_code,' . $id,
            'title'        => 'sometimes|string|max:255',
            'credit_hours' => 'sometimes|integer|min:1|max:6',
        ]);
        return response()->json(['updated' => $this->courses->update($id, $data)]);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json(['deleted' => $this->courses->delete($id)]);
    }

    public function assignStudents(Request $request, int $courseId): JsonResponse
    {
        $data = $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
            'semester'      => 'required|string|max:20',
        ]);

        $result = $this->courses->bulkAssignStudents($courseId, $data['student_ids'], $data['semester']);

        return response()->json([
            'message'          => "Assigned {$result['assigned_count']} student(s) to course.",
            'assigned_count'   => $result['assigned_count'],
            'conflict_count'   => $result['conflict_count'],
            'conflict_students' => $result['conflict_students'],
        ], 201);
    }

    public function assignInstructors(Request $request, int $courseId): JsonResponse
    {
        $data = $request->validate([
            'instructor_ids'   => 'required|array|size:1',   // exactly one instructor
            'instructor_ids.*' => 'required|integer|exists:instructors,id',
        ]);

        $count = $this->courses->bulkAssignInstructors($courseId, $data['instructor_ids']);

        return response()->json([
            'message' => $count > 0 ? 'Instructor assigned to course.' : 'No assignment applied.',
            'count'   => $count,
        ], 201);
    }

    // ... getAvailableStudents, getAssignedStudents, validateStudentsAssignment,
    //     revokeStudents, getAvailableInstructors, getAssignedInstructors, revokeInstructors
    // follow the same pattern — validate, delegate to repository, return JSON.
}
```

### Simple CRUD Controllers

`InstructorController`, `ClassroomController`, and `DepartmentController` follow the exact same pattern: inject repository interface, validate, delegate. Create them with `php artisan make:controller` and implement `index`, `store`, `show`, `update`, `destroy`.

---

## 10. Defining API Routes

```php
// routes/api.php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\StudentController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login',    [AuthController::class, 'login']);

// Protected routes — require a valid Sanctum token
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::prefix('v1')->group(function () {
        // Students
        Route::apiResource('students', StudentController::class);
        Route::post('students/{student}/enroll',  [StudentController::class, 'enroll']);
        Route::patch('students/{student}/grade',  [StudentController::class, 'updateGrade']);
        Route::get('students/{student}/report',   [StudentController::class, 'masterReport']);
        Route::post('reports/master',             [StudentController::class, 'masterReportBuilder']);

        // Courses + enrollment management
        Route::apiResource('courses', CourseController::class);
        Route::get('courses/{course}/available-students',         [CourseController::class, 'getAvailableStudents']);
        Route::get('courses/{course}/assigned-students',          [CourseController::class, 'getAssignedStudents']);
        Route::post('courses/{course}/validate-students-assignment', [CourseController::class, 'validateStudentsAssignment']);
        Route::post('courses/{course}/assign-students',           [CourseController::class, 'assignStudents']);
        Route::post('courses/{course}/revoke-students',           [CourseController::class, 'revokeStudents']);
        Route::get('courses/{course}/available-instructors',      [CourseController::class, 'getAvailableInstructors']);
        Route::get('courses/{course}/assigned-instructors',       [CourseController::class, 'getAssignedInstructors']);
        Route::post('courses/{course}/assign-instructors',        [CourseController::class, 'assignInstructors']);
        Route::post('courses/{course}/revoke-instructors',        [CourseController::class, 'revokeInstructors']);

        // Supporting resources
        Route::apiResource('instructors', InstructorController::class);
        Route::apiResource('classrooms',  ClassroomController::class);
        Route::apiResource('departments', DepartmentController::class);
    });
});
```

> **Quick reference — `apiResource` generates**:
> `GET /students` → `index`
> `POST /students` → `store`
> `GET /students/{student}` → `show`
> `PUT/PATCH /students/{student}` → `update`
> `DELETE /students/{student}` → `destroy`

---

## 11. Service Providers & Dependency Injection

### RepositoryServiceProvider

Create a dedicated service provider to bind every interface to its concrete class. This is the only place in the entire application where `StudentRepository` is mentioned explicitly — controllers never reference a concrete class.

```bash
php artisan make:provider RepositoryServiceProvider
```

```php
// app/Providers/RepositoryServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Contracts\StudentRepositoryInterface::class,
            \App\Repositories\StudentRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\CourseRepositoryInterface::class,
            \App\Repositories\CourseRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\InstructorRepositoryInterface::class,
            \App\Repositories\InstructorRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\ClassroomRepositoryInterface::class,
            \App\Repositories\ClassroomRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\DepartmentRepositoryInterface::class,
            \App\Repositories\DepartmentRepository::class
        );
    }
}
```

Register it in `bootstrap/providers.php`:

```php
// bootstrap/providers.php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
];
```

### AppServiceProvider — Query Logging

The `AppServiceProvider` optionally logs every MySQL query to a dedicated log channel, controlled by environment variables:

```php
// app/Providers/AppServiceProvider.php
namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ((bool) env('MYSQL_QUERY_LOG_ENABLED', $this->app->environment('local'))) {
            $connection = (string) env('MYSQL_QUERY_LOG_CONNECTION', 'mysql');
            $channel    = (string) env('MYSQL_QUERY_LOG_CHANNEL', 'mysql_queries');

            DB::listen(function (QueryExecuted $query) use ($connection, $channel): void {
                if ($query->connectionName !== $connection) {
                    return;
                }

                Log::channel($channel)->info('mysql.query', [
                    'connection' => $query->connectionName,
                    'time_ms'    => $query->time,
                    'sql'        => $query->sql,
                    'bindings'   => $query->bindings,
                ]);
            });
        }
    }
}
```

---

## 12. Redis as Queue Driver & Cache Store

### Configuring `config/queue.php`

Ensure the `redis` driver is configured:

```php
'redis' => [
    'driver'      => 'redis',
    'connection'  => env('REDIS_QUEUE_CONNECTION', 'default'),
    'queue'       => env('REDIS_QUEUE', 'default'),
    'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
    'block_for'   => null,
    'after_commit' => false,
],
```

And in `.env`:

```dotenv
QUEUE_CONNECTION=redis
```

### Configuring `config/cache.php`

```dotenv
CACHE_STORE=redis
```

### How Redis Queues Work Under the Hood

When a job is dispatched with `->onConnection('redis')`, Laravel serializes the job to JSON and pushes it onto a Redis list key (`queues:{queueName}`). Workers call `BRPOP` to block-wait for new jobs, pop one off atomically, and execute it. If the job fails, it moves to a `queues:{queueName}:failed` sorted set. This is significantly faster than the database queue driver because Redis operations are O(1) and in-memory.

---

## 13. Queue Jobs for Bulk Operations

### 13.1 InsertUsersChunkJob — Bulk User Generation

This job inserts a chunk of users as a single batch `INSERT`. It accepts pre-computed schema flags from the dispatching command so each worker does not re-query the `information_schema`.

```bash
php artisan make:job InsertUsersChunkJob
```

```php
// app/Jobs/InsertUsersChunkJob.php
namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class InsertUsersChunkJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    /**
     * @param  int     $startIndex   1-based index for deterministic email addresses.
     * @param  int     $chunkSize    Number of users to insert in this job.
     * @param  string  $runId        Unique identifier for this generation run.
     * @param  string  $passwordHash Pre-hashed password shared across all generated users.
     * @param  array   $columnFlags  Pre-computed booleans for schema column existence.
     */
    public function __construct(
        public int    $startIndex,
        public int    $chunkSize,
        public string $runId,
        public string $passwordHash,
        public array  $columnFlags = [],
    ) {}

    public function handle(): void
    {
        $flags     = $this->columnFlags;
        $timestamp = now();
        $rows      = [];

        for ($offset = 0; $offset < $this->chunkSize; $offset++) {
            $index = $this->startIndex + $offset;
            $email = "queued-{$this->runId}-{$index}@example.test";

            $row = [];

            if ($flags['name'] ?? false)              $row['name']              = "Queued User {$index}";
            if ($flags['email'] ?? false)             $row['email']             = $email;
            if ($flags['email_verified_at'] ?? false) $row['email_verified_at'] = $timestamp;
            if ($flags['password'] ?? false)          $row['password']          = $this->passwordHash;
            if ($flags['remember_token'] ?? false)    $row['remember_token']    = null;
            if ($flags['created_at'] ?? false)        $row['created_at']        = $timestamp;
            if ($flags['updated_at'] ?? false)        $row['updated_at']        = $timestamp;

            if (! empty($row)) {
                $rows[] = $row;
            }
        }

        if (! empty($rows)) {
            DB::table('users')->insert($rows);
        }
    }
}
```

### 13.2 SendEmailVerificationChunkJob — Batched Email Dispatch

This job uses Laravel's **Job Batching** feature (`Batchable` trait) so the parent command can track overall progress. It uses `DB::table()->lazy()` for memory-efficient cursor-based iteration over large user sets.

```bash
php artisan make:job SendEmailVerificationChunkJob
```

```php
// app/Jobs/SendEmailVerificationChunkJob.php
namespace App\Jobs;

use App\Notifications\QueuedEmailVerificationNotification;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendEmailVerificationChunkJob implements ShouldQueue
{
    use Batchable, Queueable;

    /** @var list<string> Whitelisted column names — prevents column-injection attacks */
    public const ALLOWED_ID_COLUMNS    = ['id', 'user_id', 'uuid'];
    public const ALLOWED_EMAIL_COLUMNS = ['email', 'email_address'];

    public int $timeout = 120;

    /** @param  list<int>  $userIds */
    public function __construct(
        public array  $userIds,
        public string $idColumn,
        public string $emailColumn,
    ) {
        // Validate at construction time, not just at the command level
        if (! in_array($idColumn, self::ALLOWED_ID_COLUMNS, true)) {
            throw new \InvalidArgumentException("Column '{$idColumn}' is not an allowed id column.");
        }
        if (! in_array($emailColumn, self::ALLOWED_EMAIL_COLUMNS, true)) {
            throw new \InvalidArgumentException("Column '{$emailColumn}' is not an allowed email column.");
        }
    }

    public function handle(): void
    {
        DB::table('users')
            ->whereIn($this->idColumn, $this->userIds)
            ->whereNotNull($this->emailColumn)
            ->orderBy($this->idColumn)
            ->lazy(100, $this->idColumn)       // streams 100 rows at a time, not all at once
            ->each(function (object $user): void {
                $email      = (string) data_get($user, $this->emailColumn);
                $identifier = (string) data_get($user, $this->idColumn);

                if ($email === '') return;

                // Anonymous notifiable — no User model required
                Notification::route('mail', $email)
                    ->notify(new QueuedEmailVerificationNotification($identifier, $email));
            });
    }
}
```

### 13.3 GenerateStudentsChunkJob — Bulk Student + Enrollment Creation

This job generates students and their enrollments in bulk. It uses a `courseScheduleMap` (pre-computed once at dispatch time) to avoid schedule conflicts during the seeding process.

```php
// app/Jobs/GenerateStudentsChunkJob.php
namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class GenerateStudentsChunkJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    private const SEMESTERS = ['Fall 2024', 'Spring 2025', 'Fall 2025', 'Spring 2026'];
    private const GRADES    = ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D', 'F', null];

    /**
     * @param  array<int>                        $courseIds          Pool of course IDs.
     * @param  array<int, array<array{day_of_week: string, start_time: string}>>  $courseScheduleMap
     */
    public function __construct(
        public int    $startIndex,
        public int    $chunkSize,
        public string $runId,
        public array  $courseIds,
        public array  $courseScheduleMap,
        public int    $enrollmentsPerStudent = 4,
    ) {}

    public function handle(): void
    {
        if (empty($this->courseIds)) return;

        $timestamp = now();
        $coursePool = $this->courseIds;
        $poolSize   = count($coursePool);
        $enrolCount = min($this->enrollmentsPerStudent, $poolSize);

        // --- Build students ---
        $studentRows = [];
        for ($offset = 0; $offset < $this->chunkSize; $offset++) {
            $index = $this->startIndex + $offset;
            $studentRows[] = [
                'name'       => "Student {$index}",
                'email'      => "student-{$this->runId}-{$index}@university.edu",
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::table('students')->insert($studentRows);

        $emails     = array_column($studentRows, 'email');
        $studentIds = DB::table('students')->whereIn('email', $emails)->pluck('id')->all();

        if (empty($studentIds)) return;

        // --- Build enrollments ---
        $enrollmentRows = [];
        foreach ($studentIds as $studentId) {
            $picked               = $this->pickDistinct($coursePool, $poolSize, $enrolCount);
            $usedSlotsBySemester  = [];

            foreach ($picked as $courseId) {
                $semester = $this->pickNonConflictingSemester($courseId, self::SEMESTERS, $usedSlotsBySemester);
                if ($semester === null) continue;

                $enrollmentRows[] = [
                    'student_id' => $studentId,
                    'course_id'  => $courseId,
                    'semester'   => $semester,
                    'grade'      => self::GRADES[array_rand(self::GRADES)],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                foreach (($this->courseScheduleMap[$courseId] ?? []) as $slot) {
                    $usedSlotsBySemester[$semester][$slot['day_of_week'] . '|' . $slot['start_time']] = true;
                }
            }
        }

        if (! empty($enrollmentRows)) {
            DB::table('enrollments')->insert($enrollmentRows);
        }
    }

    private function pickDistinct(array $pool, int $poolSize, int $count): array
    {
        if ($count >= $poolSize) return $pool;
        $keys = array_rand($pool, $count);
        return array_map(fn ($k) => $pool[$k], (array) $keys);
    }

    private function pickNonConflictingSemester(int $courseId, array $semesters, array $usedSlotsBySemester): ?string
    {
        $courseSlots = $this->courseScheduleMap[$courseId] ?? [];
        shuffle($semesters);

        foreach ($semesters as $semester) {
            $hasConflict = false;
            foreach ($courseSlots as $slot) {
                if (! empty($usedSlotsBySemester[$semester][$slot['day_of_week'] . '|' . $slot['start_time']])) {
                    $hasConflict = true;
                    break;
                }
            }
            if (! $hasConflict) return $semester;
        }

        return null;
    }
}
```

---

## 14. Artisan Commands for Queue Dispatch

### QueueGenerateUsersCommand

This command slices a large user-generation request into chunks and dispatches one job per chunk. Schema introspection is done **once** before dispatching, not once per worker.

```bash
php artisan make:command QueueGenerateUsersCommand
```

```php
// app/Console/Commands/QueueGenerateUsersCommand.php
namespace App\Console\Commands;

use App\Jobs\InsertUsersChunkJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class QueueGenerateUsersCommand extends Command
{
    protected $signature = 'users:queue-generate
        {--total=1000000 : Total users to generate}
        {--chunk=1000    : Users per job}
        {--connection=redis : Queue connection}
        {--queue=user-imports : Queue name}
        {--run-id=       : Optional run identifier}';

    protected $description = 'Queue generation of many users in chunked jobs';

    public function handle(): int
    {
        $total      = (int) $this->option('total');
        $chunk      = (int) $this->option('chunk');
        $connection = (string) $this->option('connection');
        $queue      = (string) $this->option('queue');
        $runId      = (string) ($this->option('run-id') ?: Str::lower(Str::ulid()->toBase32()));

        if ($total < 1)              { $this->error('--total must be > 0'); return self::FAILURE; }
        if ($chunk < 1 || $chunk > 10000) { $this->error('--chunk must be 1–10000'); return self::FAILURE; }

        $columnFlags  = $this->detectColumnFlags();
        $passwordHash = Hash::make('password');
        $jobs         = (int) ceil($total / $chunk);

        for ($index = 1; $index <= $total; $index += $chunk) {
            $chunkSize = min($chunk, $total - $index + 1);
            InsertUsersChunkJob::dispatch($index, $chunkSize, $runId, $passwordHash, $columnFlags)
                ->onConnection($connection)
                ->onQueue($queue);
        }

        $this->info("Queued {$total} users as {$jobs} jobs on {$connection}:{$queue}.");
        $this->line("Run id: {$runId}");

        return self::SUCCESS;
    }

    private function detectColumnFlags(): array
    {
        $t = 'users';
        return [
            'name'              => Schema::hasColumn($t, 'name'),
            'email'             => Schema::hasColumn($t, 'email'),
            'email_verified_at' => Schema::hasColumn($t, 'email_verified_at'),
            'password'          => Schema::hasColumn($t, 'password'),
            'remember_token'    => Schema::hasColumn($t, 'remember_token'),
            'created_at'        => Schema::hasColumn($t, 'created_at'),
            'updated_at'        => Schema::hasColumn($t, 'updated_at'),
        ];
    }
}
```

**Usage:**
```bash
php artisan users:queue-generate --total=500000 --chunk=500
php artisan queue:work redis --queue=user-imports --tries=3
```

### QueueEmailVerificationsCommand

Uses Laravel's **Job Batching** to dispatch verification emails in chunks and track overall progress.

```bash
php artisan make:command QueueEmailVerificationsCommand
```

```php
// app/Console/Commands/QueueEmailVerificationsCommand.php
namespace App\Console\Commands;

use App\Jobs\SendEmailVerificationChunkJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueEmailVerificationsCommand extends Command
{
    protected $signature = 'users:queue-email-verifications
        {--chunk=1000        : Users per job}
        {--connection=redis  : Queue connection}
        {--queue=email-verifications : Queue name}
        {--id-column=id      : Primary key column}
        {--email-column=email : Email column}
        {--email-like=       : Filter by email pattern}
        {--limit=0           : Max users (0 = all)}
        {--only-unverified=0 : 1 = target only unverified users}';

    protected $description = 'Dispatch a batch of email verification notifications';

    public function handle(): int
    {
        $chunk        = (int)    $this->option('chunk');
        $connection   = (string) $this->option('connection');
        $queue        = (string) $this->option('queue');
        $idColumn     = (string) $this->option('id-column');
        $emailColumn  = (string) $this->option('email-column');
        $emailLike    = (string) $this->option('email-like');
        $limit        = (int)    $this->option('limit');
        $onlyUnverified = (bool) ((int) $this->option('only-unverified'));

        // Validate column names against whitelist before touching the database
        if (! in_array($idColumn, SendEmailVerificationChunkJob::ALLOWED_ID_COLUMNS, true)) {
            $this->error("'{$idColumn}' is not an allowed id column."); return self::FAILURE;
        }
        if (! in_array($emailColumn, SendEmailVerificationChunkJob::ALLOWED_EMAIL_COLUMNS, true)) {
            $this->error("'{$emailColumn}' is not an allowed email column."); return self::FAILURE;
        }

        $query = DB::table('users')->select($idColumn);
        if ($emailLike)    $query->where($emailColumn, 'like', $emailLike);
        if ($onlyUnverified && Schema::hasColumn('users', 'email_verified_at')) {
            $query->whereNull('email_verified_at');
        }
        if ($limit > 0) $query->limit($limit);

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->warn('No users matched; no batch dispatched.');
            return self::SUCCESS;
        }

        // Create an empty batch first, then add jobs as we chunk through the query
        $batch    = Bus::batch([])
            ->name('email-verification-notifications')
            ->allowFailures()
            ->onConnection($connection)
            ->onQueue($queue)
            ->dispatch();

        $jobCount = 0;

        $query->orderBy($idColumn)
              ->chunkById($chunk, function ($users) use (&$jobCount, $batch, $idColumn, $emailColumn): void {
                  $ids = $users->pluck($idColumn)->map(fn ($id) => (int) $id)->all();
                  $batch->add([new SendEmailVerificationChunkJob($ids, $idColumn, $emailColumn)]);
                  $jobCount++;
              }, $idColumn);

        $this->info("Dispatched batch {$batch->id} for {$total} users as {$jobCount} jobs.");

        return self::SUCCESS;
    }
}
```

---

## 15. Email Verification Notification

The `QueuedEmailVerificationNotification` generates a **time-limited HMAC-signed URL** using Laravel's `URL::temporarySignedRoute()`. The signature prevents URL forgery — only the server can generate a valid link, and it expires after 24 hours.

```bash
php artisan make:notification QueuedEmailVerificationNotification
```

```php
// app/Notifications/QueuedEmailVerificationNotification.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class QueuedEmailVerificationNotification extends Notification
{
    use Queueable;

    private const EXPIRES_IN_HOURS = 24;

    public function __construct(
        public string $userIdentifier,
        public string $email,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // temporarySignedRoute generates a URL with an expiry timestamp and
        // an HMAC signature derived from APP_KEY. The route handler calls
        // abort_unless($request->hasValidSignature(), 403) to verify it.
        $verificationUrl = URL::temporarySignedRoute(
            'email.verify',
            now()->addHours(self::EXPIRES_IN_HOURS),
            ['user' => $this->userIdentifier],
        );

        return (new MailMessage)
            ->subject('Verify your email address')
            ->line('Please verify your email address to complete account setup.')
            ->line('User reference: ' . $this->userIdentifier)
            ->action('Verify Email', $verificationUrl)
            ->line('This link expires in ' . self::EXPIRES_IN_HOURS . ' hours.');
    }
}
```

The corresponding route in `routes/web.php`:

```php
Route::get('/email/verify', function (Request $request) {
    abort_unless($request->hasValidSignature(), 403);
    return response()->json(['message' => 'Email verified successfully.']);
})->name('email.verify');
```

---

## 16. Laravel Horizon — Queue Monitoring

Horizon provides a beautiful dashboard at `/horizon` that shows real-time queue metrics, job throughput, failure rates, and worker process status — all stored in Redis.

### Install & Publish

```bash
php artisan horizon:install
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
```

### HorizonServiceProvider — Gate Authorization

By default, Horizon is only accessible in the `local` environment. Add a gate check to allow specific users in production.

```php
// app/Providers/HorizonServiceProvider.php
namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            return in_array($user->email, [
                'admin@yourdomain.com',
            ]);
        });
    }
}
```

### Running Horizon

```bash
php artisan horizon
```

> Horizon replaces `php artisan queue:work` for Redis queues. It reads its supervisor/queue configuration from `config/horizon.php` and automatically manages worker processes.

Access the dashboard at: `http://localhost/horizon`

---

## 17. Redis Pub/Sub Dashboard Service

The `RedisDashboardSummaryService` is the heart of the real-time dashboard. It has three responsibilities:

1. **Read** — return a cached summary from Redis (falling back to a live DB query if no cache exists)
2. **Write** — recalculate a summary, store it in Redis with a 1-hour TTL, and publish an "updated" event
3. **Subscribe** — block-subscribe to a "refresh" channel so a long-running listener can re-build data on demand

```php
// app/Services/RedisDashboardSummaryService.php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RedisDashboardSummaryService
{
    // Redis key names for cached summaries
    public const QUEUE_SUMMARY_KEY  = 'dashboard:queue_summary';
    public const USERS_SUMMARY_KEY  = 'dashboard:users_summary';

    // Pub/Sub channel names
    public const REFRESH_CHANNEL = 'dashboard.summary.refresh';
    public const UPDATED_CHANNEL = 'dashboard.summary.updated';

    // Stale data guard — even if the listener dies, entries expire after 1 hour
    private const SUMMARY_TTL = 3600;

    // --- Public API --------------------------------------------------------

    public function getQueueSummary(): array
    {
        return $this->getSummary(self::QUEUE_SUMMARY_KEY, fn () => $this->refreshQueueSummary());
    }

    public function getUsersSummary(): array
    {
        return $this->getSummary(self::USERS_SUMMARY_KEY, fn () => $this->refreshUsersSummary());
    }

    public function refreshQueueSummary(): array
    {
        $summary = $this->buildQueueSummary();
        $this->setSummary(self::QUEUE_SUMMARY_KEY, $summary);
        $this->publish(self::UPDATED_CHANNEL, ['type' => 'queue', 'updated_at' => $summary['updated_at']]);
        return $summary;
    }

    public function refreshUsersSummary(): array
    {
        $summary = $this->buildUsersSummary();
        $this->setSummary(self::USERS_SUMMARY_KEY, $summary);
        $this->publish(self::UPDATED_CHANNEL, ['type' => 'users', 'updated_at' => $summary['updated_at']]);
        return $summary;
    }

    /**
     * Publish a refresh request — any subscribed listener will rebuild the summary.
     */
    public function publishRefresh(string $type = 'all'): void
    {
        $this->publish(self::REFRESH_CHANNEL, [
            'type'         => $type,
            'requested_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Block-subscribe to the refresh channel. This method never returns
     * (it is intended for a dedicated long-running Artisan command).
     */
    public function subscribeAndProcessRefreshRequests(?callable $onProcessed = null): void
    {
        Redis::subscribe([self::REFRESH_CHANNEL], function (string $message) use ($onProcessed): void {
            $payload = json_decode($message, true);
            $type    = is_array($payload) ? ($payload['type'] ?? 'all') : 'all';

            match ($type) {
                'queue'  => $this->refreshQueueSummary(),
                'users'  => $this->refreshUsersSummary(),
                default  => (fn () => [$this->refreshQueueSummary(), $this->refreshUsersSummary()])(),
            };

            if ($onProcessed) $onProcessed($type);
        });
    }

    // --- Private helpers ---------------------------------------------------

    private function getSummary(string $key, callable $fallback): array
    {
        return $this->getCachedSummary($key) ?? $fallback();
    }

    private function getCachedSummary(string $key): ?array
    {
        try {
            $encoded = Redis::get($key);
            if (! is_string($encoded) || $encoded === '') return null;
            $decoded = json_decode($encoded, true);
            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function setSummary(string $key, array $summary): void
    {
        try {
            // setex = SET with EXpiry — atomic, no separate EXPIRE call needed
            Redis::setex($key, self::SUMMARY_TTL, json_encode($summary, JSON_THROW_ON_ERROR));
        } catch (Throwable) {}
    }

    private function publish(string $channel, array $payload): void
    {
        try {
            Redis::publish($channel, json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (Throwable) {}
    }

    /**
     * Build queue statistics using a Redis pipeline (one round-trip) plus
     * a single GROUP BY query against the jobs table.
     */
    private function buildQueueSummary(): array
    {
        $queueNames = collect(config('queue.monitored_queues', ['default']))
            ->prepend(config('queue.connections.redis.queue', 'default'))
            ->filter()->unique()->values();

        // Pipeline: fire all LLEN / ZCARD commands in a single TCP round-trip
        $redisResults = [];
        try {
            $redisResults = Redis::pipeline(function ($pipe) use ($queueNames): void {
                foreach ($queueNames as $name) {
                    $pipe->llen("queues:{$name}");
                    $pipe->zcard("queues:{$name}:reserved");
                    $pipe->zcard("queues:{$name}:delayed");
                }
            });
        } catch (Throwable) {
            $redisResults = array_fill(0, $queueNames->count() * 3, 0);
        }

        $dbJobsByQueue = DB::table('jobs')
            ->select('queue', DB::raw('COUNT(*) as total'))
            ->groupBy('queue')
            ->pluck('total', 'queue');

        $queues = [];
        $pendingTotal = $reservedTotal = $delayedTotal = 0;

        foreach ($queueNames as $i => $queueName) {
            $base = $i * 3;
            $rp   = (int) ($redisResults[$base]     ?? 0);
            $rr   = (int) ($redisResults[$base + 1] ?? 0);
            $rd   = (int) ($redisResults[$base + 2] ?? 0);

            $pendingTotal  += $rp;
            $reservedTotal += $rr;
            $delayedTotal  += $rd;

            $queues[] = [
                'name'          => $queueName,
                'database_jobs' => (int) ($dbJobsByQueue[$queueName] ?? 0),
                'redis_pending'  => $rp,
                'redis_reserved' => $rr,
                'redis_delayed'  => $rd,
            ];
        }

        $batchTotals = DB::table('job_batches')
            ->selectRaw('COALESCE(SUM(total_jobs), 0) as total_jobs')
            ->selectRaw('COALESCE(SUM(pending_jobs), 0) as pending_jobs')
            ->selectRaw('COALESCE(SUM(failed_jobs), 0) as failed_jobs')
            ->first();

        return [
            'updated_at' => now()->toIso8601String(),
            'queues'     => $queues,
            'totals'     => [
                'redis_pending'     => $pendingTotal,
                'redis_reserved'    => $reservedTotal,
                'redis_delayed'     => $delayedTotal,
                'failed_jobs'       => DB::table('failed_jobs')->count(),
                'batch_total_jobs'  => (int) ($batchTotals->total_jobs   ?? 0),
                'batch_pending_jobs' => (int) ($batchTotals->pending_jobs ?? 0),
                'batch_failed_jobs' => (int) ($batchTotals->failed_jobs  ?? 0),
            ],
        ];
    }

    /**
     * Build user statistics with a single conditional-aggregate SQL query
     * rather than two separate COUNT() calls.
     */
    private function buildUsersSummary(): array
    {
        $user      = new User;
        $table     = $user->getTable();
        $pkColumn  = $user->getKeyName();

        $hasEVA  = Schema::hasColumn($table, 'email_verified_at');

        $counts = DB::table($table)
            ->selectRaw('COUNT(*) as total')
            ->when($hasEVA, fn ($q) => $q->selectRaw(
                'SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified'
            ))
            ->first();

        $total      = (int) ($counts->total   ?? 0);
        $verified   = $hasEVA ? (int) ($counts->verified ?? 0) : 0;
        $unverified = $hasEVA ? ($total - $verified) : $total;
        $latestUser = User::query()->latest($pkColumn)->first();

        return [
            'updated_at'      => now()->toIso8601String(),
            'total_users'     => $total,
            'verified_users'  => $verified,
            'unverified_users' => $unverified,
            'latest_user'     => $latestUser ? [
                'id'         => $latestUser->getKey(),
                'name'       => $latestUser->name,
                'email'      => $latestUser->email,
                'created_at' => optional($latestUser->created_at)->toIso8601String(),
            ] : null,
        ];
    }
}
```

### Long-Running Redis Listener Command

Create an Artisan command that calls `subscribeAndProcessRefreshRequests()` and never returns:

```bash
php artisan make:command RedisDashboardListenCommand
```

```php
// app/Console/Commands/RedisDashboardListenCommand.php
namespace App\Console\Commands;

use App\Services\RedisDashboardSummaryService;
use Illuminate\Console\Command;

class RedisDashboardListenCommand extends Command
{
    protected $signature   = 'dashboard:redis-listen';
    protected $description = 'Subscribe to Redis pub/sub and refresh dashboard summaries on demand';

    public function handle(RedisDashboardSummaryService $service): void
    {
        $this->info('Subscribed to: ' . RedisDashboardSummaryService::REFRESH_CHANNEL);

        $service->subscribeAndProcessRefreshRequests(function (string $type): void {
            $this->line("[" . now()->format('H:i:s') . "] Refreshed: {$type}");
        });
    }
}
```

---

## 18. Livewire Real-Time Dashboards

Livewire components combine a PHP class (server-side state) with a Blade view. They communicate over AJAX, re-rendering only changed parts of the DOM.

### UsersSummaryDashboard Component

```bash
php artisan make:livewire UsersSummaryDashboard
```

```php
// app/Livewire/UsersSummaryDashboard.php
namespace App\Livewire;

use App\Services\RedisDashboardSummaryService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class UsersSummaryDashboard extends Component
{
    public array $summary = [];

    /** Called once when the component first renders */
    public function mount(RedisDashboardSummaryService $summaryService): void
    {
        $this->summary = $summaryService->getUsersSummary();
    }

    /** Called by wire:poll.10s — silently updates from Redis cache */
    public function loadSummary(RedisDashboardSummaryService $summaryService): void
    {
        $this->summary = $summaryService->getUsersSummary();
    }

    /** Called by the "Refresh" button — triggers a Redis pub/sub refresh */
    public function refreshSummary(RedisDashboardSummaryService $summaryService): void
    {
        // Protect against button-mashing (max 5 requests per minute per IP)
        $key = 'refresh-users-summary:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) return;
        RateLimiter::hit($key, decaySeconds: 60);

        $summaryService->publishRefresh('users');
        $this->summary = $summaryService->refreshUsersSummary();
    }

    public function render()
    {
        return view('livewire.users-summary-dashboard')
            ->layout('layouts.dashboard', ['title' => 'Users Summary']);
    }
}
```

### Livewire View

```blade
{{-- resources/views/livewire/users-summary-dashboard.blade.php --}}
<div wire:poll.10s="loadSummary">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1">Users Data Summary</h4>
            <small class="text-body-secondary">Updated: {{ $summary['updated_at'] ?? 'n/a' }}</small>
        </div>
        <button wire:click="refreshSummary" type="button" class="btn btn-dark btn-sm">
            Refresh via Redis Pub/Sub
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $summary['total_users'] ?? 0 }}</div>
                    <div>Total Users</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $summary['verified_users'] ?? 0 }}</div>
                    <div>Verified Users</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $summary['unverified_users'] ?? 0 }}</div>
                    <div>Unverified Users</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Latest user card --}}
    <div class="card">
        <div class="card-header fw-semibold">Latest User</div>
        <div class="card-body">
            @if (! empty($summary['latest_user']))
                <table class="table table-borderless mb-0" style="max-width: 480px;">
                    <tbody>
                        <tr><th class="ps-0 text-body-secondary fw-medium" style="width:110px">ID</th><td>{{ $summary['latest_user']['id'] }}</td></tr>
                        <tr><th class="ps-0 text-body-secondary fw-medium">Name</th><td>{{ $summary['latest_user']['name'] }}</td></tr>
                        <tr><th class="ps-0 text-body-secondary fw-medium">Email</th><td>{{ $summary['latest_user']['email'] }}</td></tr>
                        <tr><th class="ps-0 text-body-secondary fw-medium">Created</th><td>{{ $summary['latest_user']['created_at'] ?? 'n/a' }}</td></tr>
                    </tbody>
                </table>
            @else
                <p class="text-body-secondary mb-0">No users found.</p>
            @endif
        </div>
    </div>
</div>
```

> **`wire:poll.10s`** instructs Livewire to call `loadSummary` every 10 seconds via a small AJAX request. The component reads the Redis-cached summary, so the database is only queried when the cache is cold or a manual refresh is triggered.

Create `QueueSummaryDashboard` with the same structure, calling `getQueueSummary()` and rendering `queue-summary-dashboard.blade.php` with queue-specific stats.

---

## 19. Web Routes & Livewire Wiring

```php
// routes/web.php
use App\Http\Controllers\SitemapController;
use App\Livewire\QueueSummaryDashboard;
use App\Livewire\UsersSummaryDashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', fn () => view('welcome'))->name('welcome');

// SEO Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap',     [SitemapController::class, 'sitemap'])->name('sitemap');

// Email verification endpoint (signed URL)
Route::get('/email/verify', function (Request $request) {
    abort_unless($request->hasValidSignature(), 403);
    return response()->json(['message' => 'Email verified successfully.']);
})->name('email.verify');

// Protected Livewire dashboards
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard/queue', QueueSummaryDashboard::class)->name('dashboard.queue');
    Route::get('/dashboard/users', UsersSummaryDashboard::class)->name('dashboard.users');
});

// React SPA — all /university/* routes served by the same blade view
Route::get('/university/login', fn () => view('university'))->name('login');
Route::get('/login', fn () => redirect()->away('/university/login'));
Route::get('/university/{any?}', fn() => view('university'))->where('any', '.*');
```

> The `Route::get('/university/{any?}')` catch-all route lets React Router handle navigation on the client side. The `name('login')` on the `/university/login` route is required by Laravel's auth middleware, which redirects unauthenticated requests to the named `login` route.

---

## 20. React SPA Frontend (Vite + CoreUI)

The React SPA lives in `resources/js/university/`. It communicates with the Laravel API using Axios and uses CoreUI for pre-built admin-panel components.

### Vite Configuration

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/university/main.jsx',
            ],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        proxy: {
            // Forward all non-Vite requests to Laravel's dev server
            '^(?!/@|/resources|/node_modules)': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
        },
    },
});
```

### Entry Point

```jsx
// resources/js/university/main.jsx
import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import App from './App';
import '@coreui/coreui/dist/css/coreui.min.css';

ReactDOM.createRoot(document.getElementById('university-root')).render(
    <React.StrictMode>
        <BrowserRouter basename="/university">
            <App />
        </BrowserRouter>
    </React.StrictMode>
);
```

### The Blade Host Template

```blade
{{-- resources/views/university.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>University Management</title>
    @viteReactRefresh
    @vite(['resources/js/university/main.jsx'])
</head>
<body>
    <div id="university-root"></div>
</body>
</html>
```

### API Axios Client

Create a shared Axios instance that attaches the Sanctum token from `localStorage`:

```javascript
// resources/js/university/api.js
import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1',
    headers: { 'Content-Type': 'application/json' },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('sanctum_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default api;
```

---

## 21. SEO — Sitemap, Meta Tags & Structured Data

### SeoService

```php
// app/Services/SeoService.php
namespace App\Services;

class SeoService
{
    private string $title       = '';
    private string $description = '';
    private array  $keywords    = [];
    private string $canonical   = '';

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function addKeyword(string $keyword): static
    {
        $this->keywords[] = $keyword;
        return $this;
    }

    public function setCanonical(string $url): static
    {
        $this->canonical = $url;
        return $this;
    }

    public function getMetadata(): array
    {
        return [
            'title'       => $this->title,
            'description' => $this->description,
            'keywords'    => implode(', ', $this->keywords),
            'canonical'   => $this->canonical,
        ];
    }
}
```

### SitemapController

```php
// app/Http/Controllers/SitemapController.php
namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('sitemap', 86400, function () {
            $routes = collect(Route::getRoutes())->filter(
                fn ($route) => in_array('GET', $route->methods())
                    && ! str_starts_with($route->uri(), 'api/')
                    && ! str_starts_with($route->uri(), '_')
            );

            return view('sitemap.index', ['routes' => $routes])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
```

---

## 22. Database Seeding

### DatabaseSeeder

```php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(MasterReportSeeder::class);
    }
}
```

### MasterReportSeeder — Realistic University Data

The `MasterReportSeeder` creates a complete set of departments, instructors, classrooms, courses (with schedules), and students (with enrollments) suitable for testing the master report endpoint.

```bash
php artisan db:seed
```

The seeder:
1. Creates 5 departments (Computer Science, Mathematics, Physics, Engineering, Business)
2. Creates 10 instructors and assigns them to departments
3. Creates 8 classrooms across two buildings
4. Creates 12 courses with schedules
5. Assigns one instructor per course
6. Creates 30 students with realistic enrollments (respecting schedule conflicts)

---

## 23. Testing

The application includes both unit and feature tests using PHPUnit.

### Unit Test — RedisDashboardSummaryService

```php
// tests/Unit/RedisDashboardSummaryServiceTest.php
namespace Tests\Unit;

use App\Services\RedisDashboardSummaryService;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RedisDashboardSummaryServiceTest extends TestCase
{
    public function test_get_queue_summary_returns_cached_data(): void
    {
        $cached = ['updated_at' => '2024-01-01T00:00:00+00:00', 'totals' => [], 'queues' => []];

        Redis::shouldReceive('get')
            ->once()
            ->with(RedisDashboardSummaryService::QUEUE_SUMMARY_KEY)
            ->andReturn(json_encode($cached));

        $service = new RedisDashboardSummaryService();
        $result  = $service->getQueueSummary();

        $this->assertSame($cached, $result);
    }
}
```

### Feature Test — InsertUsersChunkJob

```php
// tests/Feature/InsertUsersChunkJobTest.php
namespace Tests\Feature;

use App\Jobs\InsertUsersChunkJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InsertUsersChunkJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_inserts_correct_number_of_users(): void
    {
        $table = 'users';
        $flags = [
            'name'              => Schema::hasColumn($table, 'name'),
            'email'             => Schema::hasColumn($table, 'email'),
            'email_verified_at' => Schema::hasColumn($table, 'email_verified_at'),
            'password'          => Schema::hasColumn($table, 'password'),
            'remember_token'    => Schema::hasColumn($table, 'remember_token'),
            'created_at'        => Schema::hasColumn($table, 'created_at'),
            'updated_at'        => Schema::hasColumn($table, 'updated_at'),
        ];

        $job = new InsertUsersChunkJob(
            startIndex:   1,
            chunkSize:    10,
            runId:        'test-run',
            passwordHash: bcrypt('password'),
            columnFlags:  $flags,
        );

        $job->handle();

        $this->assertDatabaseCount('users', 10);
        $this->assertDatabaseHas('users', ['email' => 'queued-test-run-1@example.test']);
    }
}
```

### Running Tests

```bash
composer test
# or
php artisan test
```

---

## 24. Running the Full Stack

### Development (all services via Concurrently)

```bash
composer run dev
```

This single command starts:
- `php artisan serve` — Laravel HTTP server on port 8000
- `php artisan queue:listen --tries=1` — queue worker
- `npm run dev` — Vite dev server on port 5173

Open `http://localhost:5173` — the Vite dev server proxies all non-asset requests to Laravel.

### Dashboard Mode (with Redis listener)

```bash
composer run dev:dashboard
```

This starts:
- Laravel HTTP server
- `php artisan dashboard:redis-listen` — the pub/sub listener

### Starting Horizon

```bash
php artisan horizon
```

Visit `http://localhost/horizon` for the queue dashboard.

### API Usage Examples

```bash
# Register a user
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Alice","email":"alice@example.com","password":"password","password_confirmation":"password"}'

# Login
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@example.com","password":"password"}'

# Create a student (authenticated)
curl -X POST http://localhost/api/v1/students \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Bob Smith","email":"bob@university.edu"}'

# Generate 100,000 users via queue
php artisan users:queue-generate --total=100000 --chunk=500
php artisan queue:work redis --queue=user-imports

# Send verification emails to unverified users
php artisan users:queue-email-verifications --only-unverified=1 --queue=email-verifications
php artisan queue:work redis --queue=email-verifications
```

---

## 25. Architecture Diagram & Summary

```
┌─────────────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                                  │
│   React SPA (CoreUI + React Router + Axios)  │  Blade + Bootstrap   │
└───────────────────────┬─────────────────────────────┬───────────────┘
                        │ HTTP                         │ HTTP
                        ▼                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     LARAVEL 12 APPLICATION                           │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │ routes/api.php          │  routes/web.php                    │   │
│  │  auth:sanctum           │  auth middleware                   │   │
│  └────────────┬────────────┴──────────────┬───────────────────── ┘  │
│               │                           │                          │
│  ┌────────────▼────────────┐  ┌──────────▼───────────────────────┐ │
│  │    API Controllers       │  │  Livewire Components             │ │
│  │ StudentController        │  │  UsersSummaryDashboard           │ │
│  │ CourseController         │  │  QueueSummaryDashboard           │ │
│  │ InstructorController     │  └──────────┬───────────────────────┘ │
│  │ DepartmentController     │             │                          │
│  │ ClassroomController      │  ┌──────────▼───────────────────────┐ │
│  │ AuthController           │  │  RedisDashboardSummaryService    │ │
│  └────────────┬─────────────┘  └──────────┬───────────────────────┘ │
│               │                           │                          │
│  ┌────────────▼─────────────────────────┐ │                          │
│  │         Repository Layer              │ │                          │
│  │  StudentRepository                    │ │                          │
│  │  CourseRepository                     │ │                          │
│  │  InstructorRepository                 │ │                          │
│  │  ClassroomRepository                  │ │                          │
│  │  DepartmentRepository                 │ │                          │
│  └────────────┬─────────────────────────┘ │                          │
│               │                           │                          │
└───────────────┼───────────────────────────┼──────────────────────────┘
                │                           │
       ┌────────▼──────────┐       ┌────────▼──────────────────────┐
       │      MySQL         │       │            Redis               │
       │  students          │       │  queues:default (LIST)         │
       │  courses           │       │  queues:user-imports (LIST)    │
       │  enrollments       │       │  queues:*:reserved (ZSET)      │
       │  instructors       │       │  queues:*:delayed (ZSET)       │
       │  departments       │       │  dashboard:queue_summary (STR) │
       │  classrooms        │       │  dashboard:users_summary (STR) │
       │  course_schedules  │       │  Pub/Sub channels:             │
       │  course_assignments│       │    dashboard.summary.refresh   │
       │  users             │       │    dashboard.summary.updated   │
       │  jobs / job_batches│       └────────────────────────────────┘
       │  failed_jobs       │
       └────────────────────┘
```

### What You Have Built

| Feature | Technology Used |
|---|---|
| RESTful University API | Laravel Controllers + Sanctum |
| Decoupled Data Layer | Repository Pattern + IoC Container |
| Bulk Data Generation | Laravel Queue Jobs + Redis |
| Real-Time Dashboard | Livewire + Redis Pub/Sub + Redis Cache |
| Queue Monitoring | Laravel Horizon |
| OAuth2 Authorization | Laravel Passport |
| Admin SPA | React 19 + CoreUI + React Router |
| Frontend Tooling | Vite 7 + Tailwind CSS 4 |
| SEO & Sitemap | SeoService + SitemapController |

### Key Concepts Reinforced

1. **Dependency Inversion** — Controllers depend on interfaces, never concrete classes.
2. **Single Responsibility** — Repository handles queries, Controller handles HTTP, Service handles business logic.
3. **Redis as a Platform** — Queue driver, cache backend, and pub/sub message broker all in one.
4. **Idempotent Bulk Writes** — `insertOrIgnore` makes job retries safe.
5. **Security by Whitelist** — Column names in jobs are validated against an explicit allowlist.
6. **Signed URLs** — Email verification links cannot be forged or replayed.
7. **Rate Limiting** — Dashboard refresh buttons are rate-limited per IP to prevent abuse.

---

*This tutorial was written based on the actual source code of `farhankarim/laravel12-redis`. All code snippets are production-grade and directly runnable.*
