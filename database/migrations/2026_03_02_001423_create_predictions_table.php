<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('district'); //1..21
            $table->decimal('probability', 5, 4);
            $table->enum('level', ['BAJO', 'MEDIO', 'ALTO']);
            $table->dateTime('predicted_at')->useCurrent();
            $table->string('model_type', 50);
            $table->string('target_type', 50);
            $table->index('district');
            $table->index('predicted_at');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};