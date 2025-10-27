<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkPost extends BaseModel
{
    use HasFactory;

    // Disable auto-incrementing
    public $incrementing = false; // Disables auto-incrementing for the ID field

    // Specify that the ID is an integer
    // protected $keyType = 'int';  // Set key type to integer (default for auto-incrementing)
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'office_id',
        'work_type_id',
        'title',
        'responsibilities',
        'max_applicants',
        'qualifications',
        'start_date',
        'end_date',
        'work_duration',
    ];

    // This work post belongs to the office
    public function office() {
        return $this->belongsTo(Office::class);
    }

    public function skills() {
        return $this->belongsToMany(Skill::class, 'work_post_skills');
    }

    public function applications() {
        return $this->hasMany(Application::class, 'work_post_id');
    }

    // This work post belongs to the work type 
    public function workType() {
        return $this->belongsTo(WorkType::class);
    }
}
