<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Atualiza registros existentes com grafia errada
        DB::statement("UPDATE games SET status = 'canceled' WHERE status = 'cancelled'");
        // Recria a check constraint com a grafia correta
        DB::statement('ALTER TABLE games DROP CONSTRAINT IF EXISTS games_status_check');
        DB::statement("ALTER TABLE games ADD CONSTRAINT games_status_check CHECK (status IN ('open','full','in_progress','completed','canceled'))");
    }

    public function down(): void
    {
        DB::statement("UPDATE games SET status = 'cancelled' WHERE status = 'canceled'");
        DB::statement('ALTER TABLE games DROP CONSTRAINT IF EXISTS games_status_check');
        DB::statement("ALTER TABLE games ADD CONSTRAINT games_status_check CHECK (status IN ('open','full','in_progress','completed','cancelled'))");
    }
};
