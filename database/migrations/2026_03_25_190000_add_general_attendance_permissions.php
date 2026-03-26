<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->boolean('can_take_general_attendance')->default(false)->after('role');
        });

        DB::table('app_users')
            ->whereIn('role', ['director', 'teacher'])
            ->update(['can_take_general_attendance' => true]);

        Schema::table('general_attendance', function (Blueprint $table) {
            $table->foreignId('registered_by')
                ->nullable()
                ->after('qr_code_id')
                ->constrained('app_users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('general_attendance', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registered_by');
        });

        Schema::table('app_users', function (Blueprint $table) {
            $table->dropColumn('can_take_general_attendance');
        });
    }
};
