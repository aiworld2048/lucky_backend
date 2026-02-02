<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fugo_game_lists', function (Blueprint $table) {
            $table->integer('status')->default(1)->after('name');
            $table->integer('hot_status')->default(1)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fugo_game_lists', function (Blueprint $table) {
            $table->dropColumn(['status']);
            $table->dropColumn(['hot_status']);
        });
    }
};
