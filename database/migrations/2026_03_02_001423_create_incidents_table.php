<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {

            $table->increments('id');
            $table->enum('type', ['ACCIDENT', 'ROADWORK', 'EVENT']);
            $table->string('title', 120)->nullable(); 
            $table->text('description')->nullable();         
            $table->decimal('lat', 9, 6);
            $table->decimal('lon', 9, 6);
            $table->boolean('active')->default(true);
            $table->dateTime('created_at')->useCurrent();  
            $table->index('active');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
