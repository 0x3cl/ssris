<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE service_requests MODIFY status ENUM('pending', 'for-service-fee', 'for-payment', 'awaiting-feedback', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE service_requests SET status = 'pending' WHERE status = 'for-service-fee'");
        DB::statement("ALTER TABLE service_requests MODIFY status ENUM('pending', 'for-payment', 'awaiting-feedback', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
