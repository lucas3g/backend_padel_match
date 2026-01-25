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
        Schema::create('match_messages', function (Blueprint $table) {
            $table->id();

            $table->uuid('match_id');
            $table->uuid('sender_id');

            $table->string('sender_name');
            $table->text('message');

            $table->foreign('match_id')
                  ->references('id')
                  ->on('matches')
                  ->cascadeOnDelete();

            $table->foreign('sender_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_messages');
    }
};
