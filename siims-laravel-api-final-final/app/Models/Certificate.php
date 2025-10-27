<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificate extends BaseModel
{

    use SoftDeletes;

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
        'name',
        'file_path',
        'issued_date',
        "student_id",
    ];

}
