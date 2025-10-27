<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends BaseModel
{

    use HasFactory, SoftDeletes;

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
        'program_id',
        'coordinator_id',
        'name',
        'limit'
    ];  

    /**
     * Summary of students: This has many students
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Student, Section>
     */
    public function students() {
        return $this->hasMany(Student::class, 'section_id');
    }

}
