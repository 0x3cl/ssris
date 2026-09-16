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
            $table->string('op_attachment')->nullable();
            $table->string('or_attachment')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rdd_requests', function (Blueprint $table) {
            $table->dropColumn(['op_attachment', 'or_attachment']);
        });
    }
};
