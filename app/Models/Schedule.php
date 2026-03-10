<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'teacher_id',
        'subject_id',
        'grade_id',
        'section_id',
        'day',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'day'        => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'schedule_id');
    }
}