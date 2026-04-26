<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('brand', 80)->nullable()->after('nickname');
            $table->string('model', 80)->nullable()->after('brand');
            $table->unsignedSmallInteger('year')->nullable()->after('model');
            $table->string('plate', 20)->nullable()->after('year');
            $table->enum('fuel_type', ['electric', 'hybrid', 'gasoline', 'diesel'])->nullable()->after('plate');
            $table->string('color_hex', 20)->nullable()->after('fuel_type');
            $table->string('color_name', 80)->nullable()->after('color_hex');

            $table->index('fuel_type');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['fuel_type']);
            $table->dropIndex(['is_default']);

            $table->dropColumn([
                'brand',
                'model',
                'year',
                'plate',
                'fuel_type',
                'color_hex',
                'color_name',
            ]);
        });
    }
};