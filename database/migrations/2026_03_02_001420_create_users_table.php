<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
{
    Schema::create('users', function (Blueprint $table) { //creamos la tabla users con sus columnas
        $table->increments('id'); //clave primaria entera autoincremental
        $table->string('name', 100);
        $table->string('mail', 100)->unique();
        $table->string('password_hash', 255);
        $table->enum('rol', ['USER', 'ADMIN']);
        $table->boolean('status')->default(true);
        $table->timestamps();;
        //índices extra para buscar rápido por columnas
        $table->index('rol');
        $table->index('status');
    });
}

public function down(): void
{
    Schema::dropIfExists('users');
}
};
