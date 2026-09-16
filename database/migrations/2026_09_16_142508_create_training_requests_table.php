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
        Schema::create('training_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')
                ->constrained()
                ->cascadeOnDelete();

            // Part 2 - Training Details
            $table->string('training_course_requested');
            $table->unsignedInteger('estimated_participants');
            $table->date('proposed_training_date');
            $table->string('proposed_training_venue');
            $table->string('beneficiary_name');
            $table->text('purpose_of_training');

            // Part 3 - to be filled up by TSD Training staff
            $table->string('assigned_trainer');
            $table->string('assigned_assistant_trainer')->nullable();
            $table->string('official_course_title');
            $table->string('approved_training_duration');
            $table->string('training_type');
            $table->string('special_type_details')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_requests');
    }
};
