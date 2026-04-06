<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
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
