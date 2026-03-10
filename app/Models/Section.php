<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'grade_id',
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'section_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'section_id');
    }
}