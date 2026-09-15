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
            $table->unsignedTinyInteger('feedback_rating')->nullable()->after('or_no');
            $table->text('feedback_message')->nullable()->after('feedback_rating');
            $table->timestamp('feedback_received_at')->nullable()->after('feedback_message');
            $table->timestamp('reminder_sent_at')->nullable()->after('feedback_received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rdd_requests', function (Blueprint $table) {
            $table->dropColumn(['feedback_rating', 'feedback_message', 'feedback_received_at', 'reminder_sent_at']);
        });
    }
};
