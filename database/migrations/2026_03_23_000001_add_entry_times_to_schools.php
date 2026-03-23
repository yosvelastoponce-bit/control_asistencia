<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Hora a partir de la cual se acepta registro (ej: 07:00)
            $table->time('entry_start')->default('07:00:00')->after('google_sheet_id');
            // Hora límite para marcar "a tiempo" (ej: 08:00)
            $table->time('entry_limit')->default('08:00:00')->after('entry_start');
            // Hora después de la cual ya no se registra asistencia (ej: 09:00)
            $table->time('entry_end')->default('09:00:00')->after('entry_limit');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['entry_start', 'entry_limit', 'entry_end']);
        });
    }
};