<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('guest_sessions', function (Blueprint $table) {
        $table->string('session_id', 64)->primary();
        $table->integer('search_count')->default(0);
    });
}

public function down(): void
{
    Schema::dropIfExists('guest_sessions');
}
};
