<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_stats', function (Blueprint $table) {
            $table->unsignedInteger('ranking_matches')->default(0)->after('losses');
            $table->unsignedInteger('ranking_wins')->default(0)->after('ranking_matches');
            $table->unsignedInteger('ranking_losses')->default(0)->after('ranking_wins');
        });
    }

    public function down(): void
    {
        Schema::table('player_stats', function (Blueprint $table) {
            $table->dropColumn(['ranking_matches', 'ranking_wins', 'ranking_losses']);
        });
    }
};
