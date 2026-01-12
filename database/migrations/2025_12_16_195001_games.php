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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();


            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->enum('game_type', ['casual', 'competitive', 'training'])->default('casual');
            $table->enum('status', ['open', 'full', 'in_progress', 'completed', 'cancelled'])->default('open');


            $table->foreignId('court_id')->nullable()->constrained()->nullOnDelete();
            $table->text('custom_location')->nullable();
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->integer('duration_minutes')->default(90);


            $table->unsignedTinyInteger('min_level')->nullable();
            $table->unsignedTinyInteger('max_level')->nullable();
            $table->unsignedTinyInteger('max_players')->default(4);
            $table->unsignedTinyInteger('current_players')->default(1);


            $table->decimal('cost_per_player', 10, 2)->nullable();
            $table->boolean('payment_required')->default(false);


            $table->unsignedTinyInteger('team1_score')->nullable();
            $table->unsignedTinyInteger('team2_score')->nullable();
            $table->unsignedTinyInteger('winner_team')->nullable();


            $table->timestamp('completed_at')->nullable();
            $table->timestamps();


            $table->index('status');
            $table->index(['scheduled_date', 'scheduled_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
