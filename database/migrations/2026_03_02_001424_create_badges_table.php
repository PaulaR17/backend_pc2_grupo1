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
        $table->string('code', 80);
        $table->dateTime('earned_at')->nullable();
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        $table->index('user_id');
        $table->index('code');
        //mejor usamos code para identificar que insignia tiene el usuario, al final como se ralacionan ya sabemos que una linea serña una badge que ya tiene el user
    });
}

public function down(): void
{
    Schema::dropIfExists('badges');
}
};
