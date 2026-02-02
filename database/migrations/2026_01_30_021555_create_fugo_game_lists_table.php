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
        Schema::create('fugo_game_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fugo_provider_id')->constrained('fugo_providers')->onDelete('cascade');
            $table->string('name');
            $table->string('image')->nullable();
            $table->string('type')->default('slot');
            $table->string('provider')->default('African Buffalo');
            $table->integer('gameId');
            $table->integer('roomId')->default(1);
            $table->timestamps();

            // Indexes for better query performance
            $table->index('fugo_provider_id');
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
        Schema::dropIfExists('fugo_game_lists');
    }
};
