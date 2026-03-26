<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneralAttendance extends Model
{
    protected $table = 'general_attendance';

    protected $fillable = [
        'student_id',
        'school_id',
        'qr_code_id',
        'registered_by',
        'date',
        'time',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class, 'qr_code_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'registered_by');
    }

    // Scope para obtener registros de una fecha específica
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    // Scope para obtener ausencias
    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }
}
