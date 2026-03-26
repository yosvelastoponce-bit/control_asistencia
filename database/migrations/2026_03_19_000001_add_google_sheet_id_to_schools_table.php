<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up(): void
    // {
    //     Schema::table('schools', function (Blueprint $table) {
    //         // Solo agregar si la columna no existe
    //         if (!Schema::hasColumn('schools', 'google_sheet_id')) {
    //             $table->string('google_sheet_id')->nullable()->after('address');
    //         }
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::table('schools', function (Blueprint $table) {
    //         if (Schema::hasColumn('schools', 'google_sheet_id')) {
    //             $table->dropColumn('google_sheet_id');
    //         }
    //     });
    // }
};