<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('address')->nullable();
            $table->string('google_sheet_id')->nullable();
            $table->timestamp('created_at')->useCurrent()->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};