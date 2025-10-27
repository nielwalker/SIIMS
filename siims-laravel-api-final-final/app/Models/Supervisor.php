<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supervisor extends Model
{
    use HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($supervisor) {
            // Delete the associated user
            $supervisor->user()->delete();
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
        'company_id',
    ];


    // This Supervisor has an office
    public function office() {
        return $this->hasOne(Office::class, 'supervisor_id');
    }

    // This Supervisor belongs to a company
    public function company() {
        return $this->belongsTo(Company::class);
    }

    // This Supervisor belongs to a user
    public function user() {
        return $this->belongsTo(User::class);
    }
}
