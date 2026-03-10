<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'school_id',
        'name',
        'dni',
        'grade_id',
        'section_id',
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

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    // Un student tiene un único qr_code (1:1)
    public function qrCode(): HasOne
    {
        return $this->hasOne(QrCode::class, 'student_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }
}