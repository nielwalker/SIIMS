<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'college_id',
        'dept_office_id',
        'chairperson_id',
        'name',
        'max_internship',
    ];

    public function students() {
        return $this->hasMany(Student::class);
    }

    public function coordinators() {
        return $this->hasMany(Coordinator::class);
    }

    // Programs belongs to College
    public function college(): BelongsTo {
        return $this->belongsTo(College::class);
    }

    // This program belongs to the office
    public function office() {
        return $this->belongsTo(Office::class, 'dept_office_id');
    }

    // This program belongs to a chairperson
    public function chairperson()
    {
        return $this->belongsTo(User::class); // Each program belongs to one chairperson
    }

}
