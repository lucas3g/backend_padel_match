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
        Schema::create('courts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('club_id')
                  ->constrained('clubs')
                  ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
                        
            $table->enum('type', ['padel', 'beach_tenis'])->default('padel');
            $table->boolean('covered')->default(false);
            
            $table->decimal('price_per_hour', 10, 2)->nullable();

            $table->json('images')->nullable();
            $table->text('main_image_url')->nullable();


            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('total_reviews')->default(0);

            $table->boolean('active')->default(true);
            $table->timestamps();            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
