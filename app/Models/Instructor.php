<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Instructor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'specialization'];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_assignments')
            ->withTimestamps();
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_faculty')
            ->withTimestamps();
    }
}
