<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('pet', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('user_id')->unique(); 
        $table->string('name', 20);
        $table->integer('level')->default(1);
        $table->integer('experience')->default(0);
        $table->string('image', 255)->nullable();
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    });
}

public function down(): void
{
    Schema::dropIfExists('pet');
}
};
