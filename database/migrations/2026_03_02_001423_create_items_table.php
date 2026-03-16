<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('items', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name', 100);
        $table->enum('type', ['HAT', 'GLASSES', 'SUIT']);
        $table->string('rarity', 30)->nullable();
        $table->string('description', 255)->nullable();
        $table->string('image', 255)->nullable();
        $table->integer('price')->default(0);
        $table->boolean('active')->default(true);
        $table->index('type');
        $table->index('active');
    });
}

public function down(): void
{
    Schema::dropIfExists('items');
}
};
