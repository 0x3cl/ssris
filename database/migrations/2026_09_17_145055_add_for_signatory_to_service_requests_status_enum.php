<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE service_requests MODIFY status ENUM('pending', 'for-signatory', 'for-service-fee', 'for-payment', 'awaiting-feedback', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("UPDATE service_requests SET status = 'pending' WHERE status = 'for-signatory'");
        DB::statement("ALTER TABLE service_requests MODIFY status ENUM('pending', 'for-service-fee', 'for-payment', 'awaiting-feedback', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
