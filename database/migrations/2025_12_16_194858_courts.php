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
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('address');
            $table->string('city', 100);
            $table->string('state', 50);
            $table->string('postal_code', 20)->nullable();


            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();


            $table->string('court_type', 50)->nullable();
            $table->string('surface_type', 50)->nullable();
            $table->boolean('has_lighting')->default(false);
            $table->boolean('has_parking')->default(false);
            $table->boolean('has_locker_room')->default(false);
            $table->boolean('has_equipment_rental')->default(false);


            $table->decimal('price_per_hour', 10, 2)->nullable();


            $table->json('images')->nullable();
            $table->text('main_image_url')->nullable();


            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_email')->nullable();
            $table->text('website_url')->nullable();


            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('total_reviews')->default(0);


            $table->boolean('is_active')->default(true);
            $table->timestamps();


            $table->index('city');
            $table->index(['latitude', 'longitude']);
            $table->index('rating');
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
