<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'grade_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'grade_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'grade_id');
    }
}