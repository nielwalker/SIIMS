<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coordinator extends Model
{
    use HasFactory, SoftDeletes;

    /* protected static function boot()
    {
        parent::boot();

        static::deleting(function ($coordinator) {
            // Delete the associated user
            $coordinator->user()->delete();
        });
    } */

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'program_id',
    ];

    // This Coordinator belongs to a user
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function students() {
        return $this->hasMany(Student::class, 'coordinator_id');
    }

    // This Coordinator belongs to a program
    public function program() {
        return $this->belongsTo(Program::class, 'program_id');
    }
}
