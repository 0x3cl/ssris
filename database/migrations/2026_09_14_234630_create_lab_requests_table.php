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
        Schema::create('lab_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('quotation_no')->unique();
            $table->timestamp('due_date');
            $table->string('test_category');
            $table->string('sample_type');
            $table->float('sub_total');
            $table->float('discount');
            $table->float('total_fee');
            $table->timestamps();
        });

        Schema::create('lab_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_request_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('test');
            $table->text('method');
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
        Schema::dropIfExists('lab_request_items');
        Schema::dropIfExists('lab_requests');
    }
};
