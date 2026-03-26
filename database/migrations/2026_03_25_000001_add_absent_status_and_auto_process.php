<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar 'absent' al enum de general_attendance
        DB::statement("ALTER TABLE general_attendance MODIFY COLUMN status ENUM('on_time', 'late', 'absent') DEFAULT 'on_time'");

        // 2. Asegurar que time sea nullable
        DB::statement("ALTER TABLE general_attendance MODIFY COLUMN time TIME NULL");

        // 3. Agregar auto_process_absences a schools si no existe
        if (!Schema::hasColumn('schools', 'auto_process_absences')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->boolean('auto_process_absences')->default(true)->after('entry_end');
            });
        }
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE general_attendance MODIFY COLUMN status ENUM('on_time', 'late') DEFAULT 'on_time'");

        if (Schema::hasColumn('schools', 'auto_process_absences')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('auto_process_absences');
            });
        }
    }
};