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
        Schema::create('training_request_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_request_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('reference_no')->unique();
            $table->timestamp('date_time');
            $table->string('particulars');
            $table->string('duration');
            $table->unsignedInteger('no_participants');
            $table->float('net_amount_due');
            $table->string('bill_no')->nullable();
            $table->string('or_no')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_request_fees');
    }
};
