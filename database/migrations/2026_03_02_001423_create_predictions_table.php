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

        // Ajusta esta lista a vuestros distritos reales
        $table->enum('district', ['CENTRO']);
        $table->decimal('probability', 5, 4);
        $table->enum('level', ['BAJO', 'MEDIO', 'ALTO']);

        // imprescindible para "latest"
        $table->dateTime('predicted_at')->useCurrent();

        $table->index('district');
        $table->index('predicted_at');
    });
}

public function down(): void
{
    Schema::dropIfExists('predictions');
}
};
