<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($student) {
            // Delete the associated user
            $student->user()->delete();
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'program_id',
        'coordinator_id',
        'company_id',
        'age',
        'date_of_birth',
        'last_applied_at',
        'student_status_id',
        'about_me',
        'has_requested_endorsement ',
    ];

    protected $casts = [
        'last_applied_at' => 'datetime',
    ];

    public function dailyEntries()  {
        return $this->hasMany(DtrEntry::class, 'student_id');
    }

    public function weeklyEntries() {
        return $this->hasMany(WeeklyEntry::class, 'student_id');
    }

    public function requestedEndorsements()
    {
        return $this->hasMany(EndorsementLetterRequest::class, 'requested_by_id');
    }

    public function includedEndorsements()
    {
        return $this->belongsToMany(
            EndorsementLetterRequest::class,
            'endorse_students',
            'student_id',
            'endorse_req_id'
        );
    }

    public function certificates(): HasMany {

        return $this->hasMany(Certificate::class, 'student_id');

    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    public function latestApplication()
    {
        return $this->hasOne(Application::class, 'student_id')->latestOfMany()->with(['workPost.office.company', 'documentSubmissions.documentStatus', 'documentSubmissions.documentType', 'workPost.office.supervisor', 'workPost.workType', 'reports']);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function studentStatus()
    {
        return $this->belongsTo(StudentStatus::class, 'student_status_id');
    }

    public function getUser()
    {
        return $this->belongsTo(User::class);
    }

    // This student belong to a coordinator
    public function coordinator()
    {
        return $this->belongsTo(Coordinator::class, 'coordinator_id');
    }

    // This student belong to a company
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // This student have many endorsement letter requests
    public function endorsementLetterRequests()
    {
        return $this->hasMany(EndorsementLetterRequest::class, 'requested_by_id');
    }

    // Relationship to Application (Student has many Applications)
    public function applications()
    {
        return $this->hasMany(Application::class, 'student_id');
    }

    // Relationship to Work Experience (Student has many Work Experieces)
    public function workExperiences(): HasMany
    {
        return $this->hasMany(WorkExperience::class, 'student_id');
    }

    // Relationship to Education (Student has many Educations)
    public function educations(): HasMany
    {
        return $this->hasMany(Education::class, 'student_id');
    }
}
