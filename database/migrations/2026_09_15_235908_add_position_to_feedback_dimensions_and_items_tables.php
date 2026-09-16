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
        Schema::table('feedback_dimensions', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('name');
        });

        Schema::table('feedback_items', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback_items', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        Schema::table('feedback_dimensions', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
