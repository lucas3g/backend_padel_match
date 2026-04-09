<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_player_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('club_position')->nullable();
            $table->integer('club_elo')->default(1000);
            $table->unsignedInteger('ranking_matches_at_club')->default(0);
            $table->unsignedInteger('ranking_wins_at_club')->default(0);
            $table->unsignedInteger('ranking_losses_at_club')->default(0);
            $table->decimal('win_rate_at_club', 5, 2)->default(0.00);
            $table->timestamp('last_computed_at')->nullable();
            $table->timestamps();

            $table->unique(['club_id', 'player_id']);
            $table->index(['club_id', 'club_position']);
            $table->index(['club_id', 'club_elo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_player_rankings');
    }
};
