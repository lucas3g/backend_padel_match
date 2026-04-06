<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->enum('disponibilidade', ['disponivel', 'machucado', 'viajando', 'licenca'])
                  ->default('disponivel')
                  ->after('is_verified');
            $table->string('motivo_indisponibilidade', 500)->nullable()->after('disponibilidade');
            $table->date('disponivel_ate')->nullable()->after('motivo_indisponibilidade');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['disponibilidade', 'motivo_indisponibilidade', 'disponivel_ate']);
        });
    }
};
