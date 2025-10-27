<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EndorseStudent extends Model
{
    use HasFactory;

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'endorse_req_id',
        'student_id',
    ];

    // Relationship to EndorsementLetterRequest
    public function endorsementLetterRequest()
    {
        return $this->belongsTo(EndorsementLetterRequest::class, 'endorse_req_id');
    }

    // Relationship to Student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

}
