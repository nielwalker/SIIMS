<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory,SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'website_url',
        'name',
        'logo_url',
    ];

    // This company belongs to a user
    public function users() {
        return $this->belongsTo(User::class);
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // This company has many offices
    public function offices(){
        return $this->hasMany(Office::class);
    }

    // This company has many supervisors
    public function supervisors() {
        return $this->hasMany(Supervisor::class);
    }
}
