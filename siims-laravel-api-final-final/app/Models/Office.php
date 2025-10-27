<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Office extends Model {
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'offices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'office_type_id',
        'company_id',
        'supervisor_id',
        'name',
        'phone_number',
        'street',
        'barangay',
        'city_municipality',
        'province',
        'postal_code',
    ];

    // This office belongs to one company
    public function company() {
        return $this->belongsTo(Company::class, 'company_id',);
    }

    // This office has a supervisor
    public function supervisor() {
        return $this->belongsTo(Supervisor::class, 'supervisor_id');
    }
    
    // This office has many work posts
    public function workPosts() {
        return $this->hasMany(WorkPost::class);
    }

    // This office has type
    public function officeType() {
        return $this->belongsTo(OfficeType::class);
    }
}
   
    