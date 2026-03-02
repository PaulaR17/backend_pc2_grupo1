<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('inventory', function (Blueprint $table) {
        $table->increments('id');

        $table->unsignedInteger('pet_id');
        $table->unsignedInteger('items_id');
        $table->integer('quantity')->default(0);

        $table->foreign('pet_id')->references('id')->on('pet')->cascadeOnDelete();
        $table->foreign('items_id')->references('id')->on('items')->cascadeOnDelete();

        // evitar filas duplicadas por (pet,item)
        $table->unique(['pet_id', 'items_id']);

        $table->index('pet_id');
        $table->index('items_id');
    });
}

public function down(): void
{
    Schema::dropIfExists('inventory');
}
};
