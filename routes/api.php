<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\UserSearchController;
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
        Route::post('reports/master', [StudentController::class, 'masterReportBuilder']);

        Route::apiResource('courses', CourseController::class);
        Route::get('courses/{course}/available-students', [CourseController::class, 'getAvailableStudents']);
        Route::get('courses/{course}/assigned-students', [CourseController::class, 'getAssignedStudents']);
        Route::post('courses/{course}/validate-students-assignment', [CourseController::class, 'validateStudentsAssignment']);
        Route::post('courses/{course}/assign-students', [CourseController::class, 'assignStudents']);
        Route::post('courses/{course}/revoke-students', [CourseController::class, 'revokeStudents']);
        Route::get('courses/{course}/available-instructors', [CourseController::class, 'getAvailableInstructors']);
        Route::get('courses/{course}/assigned-instructors', [CourseController::class, 'getAssignedInstructors']);
        Route::post('courses/{course}/assign-instructors', [CourseController::class, 'assignInstructors']);
        Route::post('courses/{course}/revoke-instructors', [CourseController::class, 'revokeInstructors']);

        Route::apiResource('instructors', InstructorController::class);
        Route::apiResource('classrooms', ClassroomController::class);
        Route::apiResource('departments', DepartmentController::class);
        Route::get('users/search', [UserSearchController::class, 'search']);
    });
});
