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
        $table->enum('type', ['HAT', 'GLASSES', 'SUIT']);
        $table->string('description', 255)->nullable();
        $table->string('image', 255)->nullable();
        $table->integer('cost');
        $table->index('type');
    });
}

public function down(): void
{
    Schema::dropIfExists('items');
}
};
