<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DtrEntry extends Model
{
    use HasFactory, SoftDeletes;

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
     'application_id',
     'student_id',
     'status_id',
     'date',
     'time_in',
     'time_out',
     'hours_received',
    ];

    public function student() {
        return $this->belongsTo(Student::class, 'student_id');
    }
    
    public function status() {
        return $this->belongsTo(TimeRecordStatus::class, 'status_id');
    }
}
