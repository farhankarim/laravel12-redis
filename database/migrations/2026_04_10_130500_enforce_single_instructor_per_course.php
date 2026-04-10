<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $duplicateCourseIds = DB::table('course_assignments')
            ->select('course_id')
            ->groupBy('course_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('course_id');

        foreach ($duplicateCourseIds as $courseId) {
            $keepInstructorId = DB::table('course_assignments')
                ->where('course_id', $courseId)
                ->orderBy('created_at')
                ->orderBy('instructor_id')
                ->value('instructor_id');

            DB::table('course_assignments')
                ->where('course_id', $courseId)
                ->where('instructor_id', '!=', $keepInstructorId)
                ->delete();
        }

        Schema::table('course_assignments', function (Blueprint $table) {
            $table->unique('course_id', 'course_assignments_course_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('course_assignments', function (Blueprint $table) {
            $table->dropUnique('course_assignments_course_id_unique');
        });
    }
};
