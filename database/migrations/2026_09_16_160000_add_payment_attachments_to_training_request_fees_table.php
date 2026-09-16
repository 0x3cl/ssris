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
        Schema::table('training_request_fees', function (Blueprint $table) {
            $table->string('bill_attachment')->nullable();
            $table->string('or_attachment')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_request_fees', function (Blueprint $table) {
            $table->dropColumn(['bill_attachment', 'or_attachment']);
        });
    }
};
