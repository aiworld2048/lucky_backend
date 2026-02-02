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
        Schema::create('game_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('agent_id')->index();
            $table->string('provider_name');
            $table->enum('game_type',['Slot','Buffalo','PoneWine','Shan'])->index();
            $table->string('wager_code')->index();
            $table->decimal('bet_amount', 12, 2);
            $table->decimal('prize_amount', 12, 2);
            $table->decimal('net_amount', 12, 2);
            $table->decimal('before_balance', 12, 2)->nullable();;
            $table->decimal('after_balance', 12, 2)->nullable();;
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_reports');
    }
};
