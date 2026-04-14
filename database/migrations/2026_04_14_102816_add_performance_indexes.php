<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Allow lookups by course_id without a full table scan.
        // The composite primary key (student_id, course_id) does not cover
        // queries that filter on course_id first (e.g. getAvailableStudents).
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index('course_id', 'enrollments_course_id_index');
            // The PK (student_id, course_id) covers single-student lookups efficiently,
            // but bulk conflict-check queries filter a batch of students by semester.
            // This index avoids scanning every semester's enrollments for each student
            // in the batch (validateStudentAssignmentConflicts).
            $table->index(['student_id', 'semester'], 'enrollments_student_semester_index');
        });

        // The primary key on department_faculty is (department_id, instructor_id).
        // JOINs and lookups on instructor_id alone cause a full table scan.
        Schema::table('department_faculty', function (Blueprint $table) {
            $table->index('instructor_id', 'department_faculty_instructor_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex('enrollments_course_id_index');
            $table->dropIndex('enrollments_student_semester_index');
        });

        Schema::table('department_faculty', function (Blueprint $table) {
            $table->dropIndex('department_faculty_instructor_id_index');
        });
    }
};
