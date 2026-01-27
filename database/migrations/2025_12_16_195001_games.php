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

            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['private', 'public'])->default('private');
            $table->dateTime('data_time');

            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('court_id')->constrained('courts')->cascadeOnDelete();
            $table->foreignId('owner_player_id')->constrained('players')->cascadeOnDelete();

            $table->text('custom_location')->nullable();

            $table->unsignedTinyInteger('min_level')->nullable();
            $table->unsignedTinyInteger('max_level')->nullable();

            $table->unsignedTinyInteger('max_players')->default(4);

            $table->enum('status', ['open', 'full', 'in_progress', 'completed', 'cancelled'])->default('open');
            
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('cost_per_player', 10, 2)->nullable();

            $table->enum('game_type', ['casual', 'competitive', 'training'])->nullable()->default('casual');            
            $table->integer('duration_minutes')->nullable()->default(90);                        

            $table->unsignedTinyInteger('team1_score')->nullable();
            $table->unsignedTinyInteger('team2_score')->nullable();
            $table->unsignedTinyInteger('winner_team')->nullable();           

            $table->timestamps();


            $table->index('status');            
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
