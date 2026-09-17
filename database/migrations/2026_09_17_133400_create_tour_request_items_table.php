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
        Schema::create('tour_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_request_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->json('testing_lab')->nullable();
            $table->json('pilot_plant')->nullable();
            $table->json('others')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_request_items');
    }
};
