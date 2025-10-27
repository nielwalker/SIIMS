<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkExperience extends Model
{
    use HasFactory, SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'job_position',
        'company_name',
        'full_address',
        'start_date',
        'end_date',
    ];

    // Each work experience belongs to a student
    public function student() {
        return $this->belongsTo(Student::class);
    }
}
