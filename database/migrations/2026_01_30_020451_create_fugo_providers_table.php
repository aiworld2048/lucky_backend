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
        Schema::create('fugo_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->nullable();
            $table->string('type')->default('slot');
            $table->string('provider')->default('African Buffalo');
            $table->integer('gameId');
            $table->integer('roomId')->default(1);
            $table->integer('jackpot')->default(1);
            $table->integer('rtp')->default(95);
            $table->integer('BuyFreeSpin')->default(0);
            $table->integer('transfer_wallet')->default(1)->comment('Transfer Wallet');
            $table->integer('seamless')->default(1);
            $table->timestamps();

            // Indexes for better query performance
            $table->index('gameId');
            $table->index('roomId');
            $table->index('provider');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fugo_providers');
    }
};
