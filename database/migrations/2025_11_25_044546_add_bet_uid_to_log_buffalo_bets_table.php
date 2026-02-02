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
            $table->string('bet_uid')->nullable()->after('member_account');
            $table->integer('room_id')->nullable()->after('buffalo_game_id');
            $table->unique('bet_uid'); // Ensure bet_uid is unique for idempotency
            $table->index('bet_uid'); // Index for faster lookups
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_buffalo_bets', function (Blueprint $table) {
            $table->dropUnique(['bet_uid']);
            $table->dropIndex(['bet_uid']);
            $table->dropColumn(['bet_uid', 'room_id']);
        });
    }
};
