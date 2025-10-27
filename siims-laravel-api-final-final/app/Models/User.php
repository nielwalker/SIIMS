<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Http\Controllers\StudentController;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, CanResetPassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'gender',
        'phone_number',
        'street',
        'barangay',
        'city_municipality',
        'province',
        'postal_code',
        'avatar',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    //
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, table: 'user_roles');
    }

    /**
     * Summary of hasRoles: Check if the user has a following roles
     * @param array $roleNames
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function hasRoles(array $roleNames = []): BelongsToMany {
        return $this->roles()->whereIn('name', $roleNames);
    }

    public function role(string $roleName) {
        return $this->roles()->where('name', $roleName);
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * 
     * Gets all the user that is company role.
     * @return bool
     */
    public function isCompany()
    {
        return $this->roles()->where('name', 'company')->exists();
    }

    // User has one Company
    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'user_id');
    }

    // User has one Supervisor
    public function supervisor(): HasOne {
        return $this->hasOne(Supervisor::class, 'id');
    }

    // User has one Student
    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'id');
    }

    // User has one Coordinator
    public function coordinator(): HasOne {
        return $this->hasOne(Coordinator::class, 'id');
    }

    // User has an office
    public function office() {
        return $this->belongsTo(Office::class, foreignKey: 'chairperson_id', ownerKey: 'id');
    }
    

    // User has 1 or 0 program
    public function program()
    {
        return $this->hasOne(Program::class, 'chairperson_id'); // One chairperson can oversee one program
    }

    // User has an college
    public function college() {
        return $this->hasOne(College::class, 'dean_id');
    }

    // User belongs to many Groups
    public function groups() {
        return $this->belongsToMany(Group::class, 'group_users');
    }
}


