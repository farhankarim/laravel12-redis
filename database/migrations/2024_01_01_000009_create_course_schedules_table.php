<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
