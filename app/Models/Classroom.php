<?php
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
