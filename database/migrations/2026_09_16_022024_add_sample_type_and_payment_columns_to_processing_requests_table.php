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
        Schema::table('processing_requests', function (Blueprint $table) {
            $table->string('sample_type')->after('sample_no');
            $table->string('op_no')->nullable()->after('total_fee');
            $table->string('or_no')->nullable()->after('op_no');
            $table->string('op_attachment')->nullable()->after('or_no');
            $table->string('or_attachment')->nullable()->after('op_attachment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('processing_requests', function (Blueprint $table) {
            $table->dropColumn(['sample_type', 'op_no', 'or_no', 'op_attachment', 'or_attachment']);
        });
    }
};
