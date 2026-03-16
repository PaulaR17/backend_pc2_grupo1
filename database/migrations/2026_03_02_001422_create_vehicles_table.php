<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
{
    Schema::create('vehicles', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('user_id');
        $table->enum('type', ['CAR', 'MOTORBIKE', 'VAN']);
        $table->unsignedInteger('vehicle_label_id')->nullable();
        $table->string('nickname', 50)->nullable();
        $table->boolean('is_electric')->default(false);
        $table->boolean('is_default')->default(false);
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        $table->foreign('vehicle_label_id')->references('id')->on('vehicle_labels')->nullOnDelete();
        $table->index('user_id');
        $table->index('vehicle_label_id');
    });
}

public function down(): void
{
    Schema::dropIfExists('vehicles');
}
};
