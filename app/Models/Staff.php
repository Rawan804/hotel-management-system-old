<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Staff extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'staff';

    protected $primaryKey = 'staff_id';

    protected $fillable = [
        'dep_id',
        'name',
        'phone',
        'email',
        'role',
        'password',
        'is_active',
        'created_by',
      'otp_code',
    'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function department()
    {
        return $this->belongsTo(
            Department::class,
            'dep_id'
        );
    }

    public function tasks()
    {
        return $this->hasMany(
            Task::class,
            'staff_id'
        );
    }

    public function leaves()
    {
        return $this->hasMany(
            LeaveRequest::class,
            'staff_id'
        );
    }

    public function complaints()
    {
        return $this->hasMany(
            Complaint::class,
            'staff_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Creator Relationships
    |--------------------------------------------------------------------------
    */

    public function creator()
    {
        return $this->belongsTo(
            Staff::class,
            'created_by',
            'staff_id'
        );
    }

    public function createdStaff()
    {
        return $this->hasMany(
            Staff::class,
            'created_by',
            'staff_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isGeneralManager(): bool
    {
        return $this->role === 'general_manager';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }
  public function serviceRequests()
{
    return $this->hasMany(
        ServiceRequest::class,
        'staff_id',
        'staff_id'
    );
}
}