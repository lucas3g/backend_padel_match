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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('full_name');
            $table->string('phone', 20)->nullable();


            $table->unsignedTinyInteger('level')->default(3);
            $table->enum('side', ['left', 'right'])->nullable();
            $table->text('bio')->nullable();
            $table->text('profile_image_url')->nullable();


            $table->integer('total_matches')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('ranking_points')->default(1000);
            $table->integer('ranking_position')->nullable();


            $table->json('preferred_locations')->nullable();
            $table->json('preferred_times')->nullable();


            $table->timestamp('last_login')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);


            $table->timestamps();


            $table->index('ranking_points');
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
