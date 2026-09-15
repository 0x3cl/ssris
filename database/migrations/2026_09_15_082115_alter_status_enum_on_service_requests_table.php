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
        DB::statement("ALTER TABLE service_requests MODIFY status ENUM('pending', 'on-going', 'payment', 'for-payment', 'awaiting-feedback', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("UPDATE service_requests SET status = 'for-payment' WHERE status IN ('on-going', 'payment')");
        DB::statement("ALTER TABLE service_requests MODIFY status ENUM('pending', 'for-payment', 'awaiting-feedback', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE service_requests MODIFY status ENUM('pending', 'on-going', 'payment', 'for-payment', 'awaiting-feedback', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("UPDATE service_requests SET status = 'on-going' WHERE status IN ('for-payment', 'awaiting-feedback')");
        DB::statement("ALTER TABLE service_requests MODIFY status ENUM('pending', 'on-going', 'payment', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
