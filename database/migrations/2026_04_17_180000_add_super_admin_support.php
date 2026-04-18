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
            if (!Schema::hasColumn('app_users', 'code')) {
                $table->string('code', 32)->nullable()->unique()->after('role');
            }
        });

        Schema::table('schools', function (Blueprint $table) {
            if (!Schema::hasColumn('schools', 'is_access_enabled')) {
                $table->boolean('is_access_enabled')->default(true)->after('logo_path');
            }
        });

        DB::statement('ALTER TABLE app_users MODIFY school_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE app_users SET school_id = 1 WHERE school_id IS NULL');
        DB::statement('ALTER TABLE app_users MODIFY school_id BIGINT UNSIGNED NOT NULL');

        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools', 'is_access_enabled')) {
                $table->dropColumn('is_access_enabled');
            }
        });

        Schema::table('app_users', function (Blueprint $table) {
            if (Schema::hasColumn('app_users', 'code')) {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            }
        });
    }
};
