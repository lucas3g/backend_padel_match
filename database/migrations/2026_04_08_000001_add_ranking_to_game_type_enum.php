<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL: recriar constraint CHECK incluindo o valor 'ranking'
        DB::statement('ALTER TABLE games DROP CONSTRAINT IF EXISTS games_game_type_check');
        DB::statement("ALTER TABLE games ADD CONSTRAINT games_game_type_check
            CHECK (game_type IN ('casual', 'competitive', 'training', 'ranking'))");
    }

    public function down(): void
    {
        DB::statement("UPDATE games SET game_type = 'casual' WHERE game_type = 'ranking'");
        DB::statement('ALTER TABLE games DROP CONSTRAINT IF EXISTS games_game_type_check');
        DB::statement("ALTER TABLE games ADD CONSTRAINT games_game_type_check
            CHECK (game_type IN ('casual', 'competitive', 'training'))");
    }
};
