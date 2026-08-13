<?php

namespace App\Models;
use Carbon\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
         'image',
        'password',
        'is_active',
        'created_by',
        'otp_code',
        'otp_expires_at',
        'status',
        'service_load',
        'max_load',
        'overloaded',
        'fcm_token'
        
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
        'otp_expires_at'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];


    protected function image(): Attribute
{
    return Attribute::make(
        get: function ($value) {
            if (!$value) return null;
            if (filter_var($value, FILTER_VALIDATE_URL)) return $value;
            
            return asset('storage/' . $value);
        }
    );
}
  
  

    public function isBusy()
    {
        return $this->status === 'busy';
    }

    public function isOffline()
    {
        return $this->status === 'offline';
    }

    public function isOnBreak()
    {
        return $this->status === 'on_break';
    }

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
        'staff_id',
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
    /*
    public function rebuildServiceLoad()
{
    $taskLoad = $this->tasks()
        ->whereIn('status', ['pending', 'in_progress'])
        ->sum('weight');

    $requestLoad = ServiceRequest::where('staff_id', $this->staff_id)
        ->whereIn('status', ['pending', 'in_progress'])
        ->sum('weight');

    $total = $taskLoad + $requestLoad;

    $this->update([
        'service_load' => $total
    ]);

    return $total;
}*/
/*
public function rebuildServiceLoad()
{
    $this->service_load = ServiceRequest::where('staff_id', $this->staff_id)
        ->whereIn('status', ['pending','in_progress'])
        ->sum('weight');

    $this->save();
}
*/

public function isOverloaded(): bool
{
    return $this->service_load >= $this->max_load;
}

public function availableCapacity(): int
{
    return max(0, $this->max_load - $this->service_load);
}
public function shifts()
{
    return $this->hasMany(StaffShift::class, 'staff_id', 'staff_id');
}


public function activeRequests()
{
    return $this->hasMany(
        ServiceRequest::class,
        'staff_id',
        'staff_id'
    )
    ->whereIn('status', [
        'pending',
        'in_progress'
    ]);
}


public function isWorkingNow()
{
    $now = now();

    return $this->shifts()
        ->where('shift_date', $now->toDateString())
        ->where('is_active', true)
        ->whereTime('start_time','<=',$now->format('H:i:s'))
        ->whereTime('end_time','>=',$now->format('H:i:s'))
        ->exists();
}
public function isAvailable()
{
    return $this->is_active 
        && in_array($this->status, ['available', 'busy']);
}

public function isInShift()
{
    $now = now();

    return $this->shifts()
        ->where('shift_date', $now->toDateString())
        ->where('is_active', true)
        ->where('start_time', '<=', $now->format('H:i:s'))
        ->where('end_time', '>=', $now->format('H:i:s'))
        ->exists();
}
public function activeServiceRequests()
{
    return $this->serviceRequests()
        ->whereIn('status', [
            'pending',
            'in_progress'
        ]);
}
public function rebuildServiceLoad()
{
    $this->service_load = $this->serviceRequests()
        ->whereIn('status', [
            'pending',
            'in_progress'
        ])
        ->sum('weight');

    $this->save();

    return $this->service_load;
}


public function notifications()
{
    return $this->hasMany(
        Notification::class,
        'staff_id',
        'staff_id'
    );
}

}