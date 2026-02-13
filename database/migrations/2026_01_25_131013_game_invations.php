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
        Schema::create('game_invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('game_id')
                  ->constrained('games')
                  ->cascadeOnDelete();

            $table->foreignId('player_id')
                  ->constrained('players')
                  ->cascadeOnDelete();

            $table->foreignId('invited_by')
                  ->constrained('players')
                  ->cascadeOnDelete();

            $table->enum('status', ['pending', 'accepted', 'rejected'])
                  ->default('pending');

            $table->timestamps();

            $table->unique(['game_id', 'player_id']);
            $table->index(['player_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_invitations');
    }
};
