<?php

use App\Enums\ClientService;
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
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->enum('service', array_column(ClientService::cases(), 'value'));
            $table->boolean('is_appointment')->default(false);
            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->date('appointment_date')->nullable();
            $table->time('appointment_time')->nullable();
            $table->enum('status', [
                'pending',
                'on-going',
                'payment',
                'completed',
                'cancelled',
            ])->default('pending');
            $table->longText('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
