<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('history', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('user_id');
        $table->decimal('origin_lat', 9, 6);
        $table->decimal('origin_lon', 9, 6);
        $table->decimal('dest_lat', 9, 6);
        $table->decimal('dest_lon', 9, 6);
        $table->decimal('distance_km', 8, 2)->nullable();
        $table->decimal('duration_min', 8, 2)->nullable();
        $table->dateTime('created_at')->useCurrent();
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        $table->index('user_id');
        $table->index('created_at');
    });
}

public function down(): void
{
    Schema::dropIfExists('history');
}

};
