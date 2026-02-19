<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dateTime('data_time')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Preenche NULLs com valor padrão antes de tornar NOT NULL
        DB::table('games')->whereNull('data_time')->update(['data_time' => now()]);

        Schema::table('games', function (Blueprint $table) {
            $table->dateTime('data_time')->nullable(false)->change();
        });
    }
};
