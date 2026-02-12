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
        Schema::create('player_favorites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('player_id')
                  ->constrained('players')
                  ->cascadeOnDelete();

            $table->foreignId('favorite_player_id')
                  ->constrained('players')
                  ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['player_id', 'favorite_player_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_favorites');
    }
};
