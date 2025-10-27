<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EndorsementLetterRequest extends BaseModel
{
    use HasFactory, SoftDeletes;


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
        'requested_by_id',
        'application_id',
        "endorsement_file",
        "description",
        "status_id",
        "work_post_id",
        "endorsement_letter_request_status_id",
        "remarks",

        // Manual
        'company_name',
        'company_address',
        'recipient_name',
        'recipient_position',
        'type',
    ];

    public function requester() {
        return $this->belongsTo(Student::class, 'requested_by_id');
    }

    public function includedStudents() {
        return $this->belongsToMany(Student::class, 'endorse_students', 'endorse_req_id');
    }


    public function application() {
        return $this->belongsTo(Application::class, 'application_id');
    }

   // Relationship with EndorseStudent
   public function endorseStudents()
   {
       return $this->hasMany(EndorseStudent::class, 'endorse_req_id');
   }

   public function workPost() {
    return $this->belongsTo(WorkPost::class, 'work_post_id');
   }

   public function endorsementLetterRequestStatus() {
        return $this->belongsTo(EndorsementLetterRequestStatus::class, 'endorsement_letter_request_status_id');
   }

   public function status() {
    return $this->belongsTo(Status::class, 'status_id');
   }

    // This endorsement letter belongs to student
    public function student() {
        return $this->belongsTo(Student::class, 'requested_by_id');
    }
}
