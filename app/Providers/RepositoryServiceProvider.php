<?php

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
