<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends BaseModel
{
    use HasFactory;

    // Disable auto-incrementing
    public $incrementing = false; // Disables auto-incrementing for the ID field

    // Specify that the ID is an integer
    // protected $keyType = 'int';  // Set key type to integer (default for auto-incrementing)
    protected $keyType = 'string';
    
    // Define your primary key
    protected $primaryKey = 'id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'work_post_id',
        'student_id',
        'status_type_id',
        'remarks',
        'applied_date',
        'application_status_id',
        'step'
    ];

    public function dtrEntries() {
        return $this->hasMany(DtrEntry::class, 'application_id');
    }

    public function applicationStatus() {
        return $this->belongsTo(ApplicationStatus::class, 'application_status_id');
    }
    
    // Each work post has a relationship to a work post
    public function workPost() {
        return $this->belongsTo(WorkPost::class, 'work_post_id');
    }

    public function student() {
        return $this->belongsTo(Student::class, 'student_id');
    } 

    public function endorsement(): HasOne {
        return $this->hasOne(EndorsementLetterRequest::class, 'application_id');
    }
    
    public function documentSubmissions(): HasMany
{
    return $this->hasMany(DocumentSubmission::class, 'application_id');
}
public function reports(): HasMany
{
    return $this->hasMany(Report::class, 'application_id');
}

}
