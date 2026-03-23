<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'code',
        'address',
        'google_sheet_id',
        'entry_start', 
        'entry_limit', 
        'entry_end',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function appUsers(): HasMany
    {
        return $this->hasMany(AppUser::class, 'school_id');
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class, 'school_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'school_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'school_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'school_id');
    }
}