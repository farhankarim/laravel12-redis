<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\StudentController;
use Illuminate\Support\Facades\Route;

Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::prefix('v1')->group(function () {
        Route::apiResource('students', StudentController::class);
        Route::post('students/{student}/enroll', [StudentController::class, 'enroll']);
        Route::patch('students/{student}/grade', [StudentController::class, 'updateGrade']);
        Route::get('students/{student}/report', [StudentController::class, 'masterReport']);

        Route::apiResource('courses', CourseController::class);
        Route::apiResource('instructors', InstructorController::class);
        Route::apiResource('classrooms', ClassroomController::class);
        Route::apiResource('departments', DepartmentController::class);
    });
});
