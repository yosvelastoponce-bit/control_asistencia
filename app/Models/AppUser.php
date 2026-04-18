<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class AppUser extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'app_users';

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'name',
        'email',
        'password',
        'role',
        'code',
        'can_take_general_attendance',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'password'   => 'hashed',
            'school_id'  => 'integer',
            'can_take_general_attendance' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function belongsToEnabledSchool(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->school_id) {
            return false;
        }

        return (bool) optional($this->school)->is_access_enabled;
    }

    public function canTakeGeneralAttendance(): bool
    {
        return (bool) $this->can_take_general_attendance;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    // Un app_user puede ser un teacher (1:1)
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'user_id');
    }

    // Un app_user puede ser un student (1:1)
    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'user_id');
    }
}
