<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('qr_code_id')->nullable()->constrained('qr_codes')->nullOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('app_users')->nullOnDelete(); // null = sistema automático
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('status', 20); // present, late, absent
            $table->timestamp('created_at')->useCurrent()->nullable();

            // Un estudiante no puede tener dos registros para el mismo horario y fecha
            $table->unique(['student_id', 'schedule_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};