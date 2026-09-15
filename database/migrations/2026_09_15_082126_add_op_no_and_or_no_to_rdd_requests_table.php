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
        Schema::table('rdd_requests', function (Blueprint $table) {
            $table->string('op_no')->nullable()->after('total_fee');
            $table->string('or_no')->nullable()->after('op_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rdd_requests', function (Blueprint $table) {
            $table->dropColumn(['op_no', 'or_no']);
        });
    }
};
