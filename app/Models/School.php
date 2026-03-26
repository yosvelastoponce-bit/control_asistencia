<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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
        'auto_process_absences',
        'logo_path'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'auto_process_absences' => 'boolean',
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

    /**
     * Verificar si el registro de asistencia está activo
     */
    public function isAttendanceActive(): bool
    {
        if (!$this->entry_start || !$this->entry_end) return false;
        
        $now = Carbon::now();
        $start = Carbon::parse($this->entry_start);
        $end = Carbon::parse($this->entry_end);
        
        return $now->between($start, $end);
    }
    
    /**
     * Verificar si ya pasó la hora de cierre
     */
    public function isAttendanceClosed(): bool
    {
        if (!$this->entry_end) return false;
        
        return Carbon::now()->gt(Carbon::parse($this->entry_end));
    }
    
    /**
     * Determinar el estado de asistencia según la hora
     */
    public function getAttendanceStatus(string $time): string
    {
        if (!$this->entry_limit) return 'late';
 
        return Carbon::parse($time)->lte(Carbon::parse($this->entry_limit))
            ? 'on_time'
            : 'late';
    }
}