<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE service_requests MODIFY status ENUM('pending', 'for-signatory', 'assign-signatories', 'for-signature', 'for-service-fee', 'for-payment', 'awaiting-feedback', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
        DB::table('service_requests')->where('status', 'for-signatory')->update(['status' => 'assign-signatories']);
        DB::table('service_requests')->where('service', 'plant-tour-services')->where('status', 'awaiting-feedback')->update(['status' => 'for-signature']);
        DB::statement("ALTER TABLE service_requests MODIFY status ENUM('pending', 'assign-signatories', 'for-signature', 'for-service-fee', 'for-payment', 'awaiting-feedback', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE service_requests MODIFY status ENUM('pending', 'for-signatory', 'assign-signatories', 'for-signature', 'for-service-fee', 'for-payment', 'awaiting-feedback', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
        DB::table('service_requests')->where('status', 'assign-signatories')->update(['status' => 'for-signatory']);
        DB::table('service_requests')->where('status', 'for-signature')->update(['status' => 'awaiting-feedback']);
        DB::statement("ALTER TABLE service_requests MODIFY status ENUM('pending', 'for-signatory', 'for-service-fee', 'for-payment', 'awaiting-feedback', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
