<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendance';

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'schedule_id',
        'qr_code_id',
        'registered_by',
        'date',
        'time',
        'status',
    ];

    protected $casts = [
        'date'       => 'date',
        'created_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class, 'qr_code_id');
    }

    // Profesor o null si fue registrado automáticamente por el sistema
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'registered_by');
    }
}