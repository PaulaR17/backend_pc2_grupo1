<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
{
    Schema::create('vehicle_labels', function (Blueprint $table) {
        $table->increments('id');
        $table->enum('name', ['0', 'ECO', 'C', 'B']);
        $table->text('description')->nullable();
    });
}
};
