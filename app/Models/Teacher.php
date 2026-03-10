<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'school_id',
        'specialty',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'teacher_id');
    }
}