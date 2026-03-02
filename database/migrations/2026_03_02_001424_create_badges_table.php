<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('badges', function (Blueprint $table) {
        $table->increments('id');

        $table->unsignedInteger('user_id');
        $table->string('name', 80);
        $table->boolean('status')->default(false);
        $table->text('description')->nullable();

        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

        $table->index('user_id');
        $table->index('status');
    });
}

public function down(): void
{
    Schema::dropIfExists('badges');
}
};
