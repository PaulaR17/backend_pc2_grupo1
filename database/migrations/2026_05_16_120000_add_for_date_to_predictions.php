<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Añade la fecha objetivo de la predicción (a qué día se refiere).
// La columna predicted_at original guardaba "cuándo se generó la predicción",
// pero faltaba "para qué día predice", que es lo que el frontend necesita
// para pintar el mapa por día.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->date('for_date')->nullable()->after('district');
            $table->index('for_date');
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropIndex(['for_date']);
            $table->dropColumn('for_date');
        });
    }
};
