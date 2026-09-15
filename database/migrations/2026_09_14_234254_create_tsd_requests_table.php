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
        Schema::create('tsd_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('reference_no')->unique();
            $table->string('sample_no')->unique();
            $table->timestamp('due_date');
            $table->float('sub_total');
            $table->float('discount');
            $table->float('total_fee');
            $table->timestamps();
        });

        Schema::create('tsd_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tsd_request_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('item');
            $table->string('weight');
            $table->integer('quantity');
            $table->float('unit_fee');
            $table->float('total_fee');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tsd_request_items');
        Schema::dropIfExists('tsd_requests');
    }
};
