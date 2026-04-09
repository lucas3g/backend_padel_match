<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->unique()->constrained('clubs')->cascadeOnDelete();
            $table->unsignedInteger('ranking_position')->nullable();
            $table->decimal('average_elo', 10, 2)->default(1000.00);
            $table->unsignedInteger('active_players')->default(0);
            $table->unsignedInteger('total_ranking_games')->default(0);
            $table->decimal('win_rate', 5, 2)->default(0.00);
            $table->timestamp('last_computed_at')->nullable();
            $table->timestamps();

            $table->index('average_elo');
            $table->index('ranking_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_rankings');
    }
};
