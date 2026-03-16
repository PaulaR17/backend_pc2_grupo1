<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('equipment', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('user_id');
        $table->string('slot', 30); //antes cada slot tenía su propia columna (ya fuera hat, glasses, suit…), ahora con slot + item para que sea más flexible y no tengamos que cambiar la tabla cada vez que añadamos algo nuevo
        $table->unsignedInteger('item_id')->nullable();
        $table->dateTime('updated_at')->nullable();
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        $table->foreign('item_id')->references('id')->on('items')->nullOnDelete();
        $table->unique(['user_id', 'slot']);
        $table->index('user_id');
        $table->index('item_id');
    });
}

public function down(): void
{
    Schema::dropIfExists('equipment');
}
};
