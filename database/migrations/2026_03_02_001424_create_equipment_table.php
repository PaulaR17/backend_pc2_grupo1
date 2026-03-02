<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('equipment', function (Blueprint $table) {
        $table->unsignedInteger('pet_id')->primary();

        $table->unsignedInteger('hat_id')->nullable();
        $table->unsignedInteger('glasses_id')->nullable();
        $table->unsignedInteger('suit_id')->nullable();

        $table->foreign('pet_id')->references('id')->on('pet')->cascadeOnDelete();
        $table->foreign('hat_id')->references('id')->on('items')->nullOnDelete();
        $table->foreign('glasses_id')->references('id')->on('items')->nullOnDelete();
        $table->foreign('suit_id')->references('id')->on('items')->nullOnDelete();

        $table->index('hat_id');
        $table->index('glasses_id');
        $table->index('suit_id');
    });
}

public function down(): void
{
    Schema::dropIfExists('equipment');
}
};
