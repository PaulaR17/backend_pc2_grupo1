<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('favorites', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('user_id');
        $table->unsignedInteger('history_id');
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete(); //clave foranea a users al id, si se borra el usuario, se borran sus favoritos
        $table->foreign('history_id')->references('id')->on('history')->cascadeOnDelete();
        //evitar duplicados: mismo user no puede guardar el mismo history 2 veces
        $table->unique(['user_id', 'history_id']);
        $table->index('user_id');
        $table->index('history_id');
    });
}
};
