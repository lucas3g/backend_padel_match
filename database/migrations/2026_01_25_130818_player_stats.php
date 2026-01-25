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
         Schema::create('player_stats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('player_id')
                  ->unique()
                  ->constrained('players')
                  ->cascadeOnDelete();

            $table->unsignedInteger('total_matches')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);

            $table->decimal('win_rate', 5, 2)->default(0.00);
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->decimal('average_elo', 10, 2)->default(1000.00);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_stats');
    }
};
