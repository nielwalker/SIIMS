<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class College extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'campus_id',
        'dean_id',
        'name',
    ];

    // This college has dean
    public function dean() {
        return $this->belongsTo(User::class, 'dean_id');
    }

    // This college has many programs

    public function programs() {
        return $this->hasMany(Program::class);
    }
}
