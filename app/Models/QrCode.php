<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrCode extends Model
{
    protected $table = 'qr_codes';

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'uuid',
        'active',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'created_at' => 'datetime',
    ];

    // Un qr_code pertenece a un student (1:1)
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}