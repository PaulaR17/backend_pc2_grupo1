<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('history', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('vehicle_labels', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('pet', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('predictions', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('badges', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('history', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('vehicle_labels', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('pet', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('predictions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('badges', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
