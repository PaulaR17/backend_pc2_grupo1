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
        $table->unsignedInteger('user_id');
        $table->unsignedInteger('item_id');
        $table->integer('quantity')->default(1);
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
        $table->unique(['user_id', 'item_id']);
        $table->index('user_id');
        $table->index('item_id');
    });
}

public function down(): void
{
    Schema::dropIfExists('inventory');
}
};
