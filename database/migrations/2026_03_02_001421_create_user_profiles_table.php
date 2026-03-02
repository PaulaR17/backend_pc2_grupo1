<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('user_profiles', function (Blueprint $table) {
        $table->unsignedInteger('user_id')->primary();
        $table->decimal('home_lat', 9, 6)->nullable();
        $table->decimal('home_lon', 9, 6)->nullable();
        $table->decimal('work_lat', 9, 6)->nullable();
        $table->decimal('work_lon', 9, 6)->nullable();
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    });

}

public function down(): void
{
    Schema::dropIfExists('user_profiles');
}

};
