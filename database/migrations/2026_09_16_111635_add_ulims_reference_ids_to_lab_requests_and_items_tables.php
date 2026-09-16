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
        Schema::table('lab_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('ulims_test_category_id')->nullable()->after('test_category');
            $table->unsignedBigInteger('ulims_sample_type_id')->nullable()->after('sample_type');
            $table->string('op_no')->nullable()->after('total_fee');
            $table->string('or_no')->nullable()->after('op_no');
            $table->string('op_attachment')->nullable()->after('or_no');
            $table->string('or_attachment')->nullable()->after('op_attachment');
        });

        Schema::table('lab_request_items', function (Blueprint $table) {
            $table->unsignedBigInteger('ulims_test_id')->nullable()->after('test');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_requests', function (Blueprint $table) {
            $table->dropColumn(['ulims_test_category_id', 'ulims_sample_type_id', 'op_no', 'or_no', 'op_attachment', 'or_attachment']);
        });

        Schema::table('lab_request_items', function (Blueprint $table) {
            $table->dropColumn('ulims_test_id');
        });
    }
};
