<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['dept_name'];

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class, 'department_faculty')
            ->withTimestamps();
    }
}
