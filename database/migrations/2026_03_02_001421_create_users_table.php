<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
{
    Schema::create('users', function (Blueprint $table) { //creamos la tabla users con sus columnas, el blueprint es para definir la estructura de la tabla
        $table->increments('id'); //al marcarlo como increments, se convierte en una clave primaria automatico y se sabe que es int tmb
        $table->string('mail', 100)->unique();
        $table->string('password_hash', 255);
        $table->enum('rol', ['USER', 'ADMIN']);
        $table->boolean('status')->default(true);
        $table->dateTime('created_at')->useCurrent();
        //índices extra para buscar rapidito por columnas y no tener que mirar TODO
        $table->index('rol');
        $table->index('status');
    });
}

public function down(): void
{
    Schema::dropIfExists('users');
}
};
