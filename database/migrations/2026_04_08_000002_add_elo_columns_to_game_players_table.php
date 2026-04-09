<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->decimal('elo_before', 10, 2)->nullable()->after('team');
            $table->decimal('elo_after', 10, 2)->nullable()->after('elo_before');
            $table->integer('elo_delta')->nullable()->after('elo_after');
        });
    }

    public function down(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->dropColumn(['elo_before', 'elo_after', 'elo_delta']);
        });
    }
};
