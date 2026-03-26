<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modificar la tabla general_attendance
        Schema::table('general_attendance', function (Blueprint $table) {
            // Hacer que el campo time sea nullable (para ausencias)
            $table->time('time')->nullable()->change();
            
            // Modificar el enum para incluir 'absent'
            $table->enum('status', ['on_time', 'late', 'absent'])->default('on_time')->change();
        });
    }

    public function down(): void
    {
        Schema::table('general_attendance', function (Blueprint $table) {
            $table->time('time')->nullable(false)->change();
            $table->enum('status', ['on_time', 'late'])->default('on_time')->change();
        });
    }
};