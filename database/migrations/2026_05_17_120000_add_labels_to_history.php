<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

//añade el nombre legible del origen y destino a las rutas guardadas para
//que el listado de historial/favoritos no muestre lat/lon crudo
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('history', function (Blueprint $table) {
            $table->string('origin_label', 255)->nullable()->after('origin_lon');
            $table->string('dest_label', 255)->nullable()->after('dest_lon');
        });
    }

    public function down(): void
    {
        Schema::table('history', function (Blueprint $table) {
            $table->dropColumn(['origin_label', 'dest_label']);
        });
    }
};
