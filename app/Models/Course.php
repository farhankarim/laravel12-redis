<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['course_code', 'title'];

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
