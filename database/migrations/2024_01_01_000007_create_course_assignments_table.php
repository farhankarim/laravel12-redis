<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_assignments', function (Blueprint $table) {
            $table->foreignId('instructor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->primary(['instructor_id', 'course_id']);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('course_assignments'); }
};
