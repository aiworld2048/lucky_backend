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
        Schema::table('log_buffalo_bets', function (Blueprint $table) {
            $table->string('player_reg_player_ref_code')->nullable()->after('player_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_buffalo_bets', function (Blueprint $table) {
            $table->dropColumn('player_reg_player_ref_code');
        });
    }
};
