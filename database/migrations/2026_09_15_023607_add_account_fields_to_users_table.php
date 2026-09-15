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
        $hasAccountStatus = Schema::hasColumn('users', 'account_status');

        Schema::table('users', function (Blueprint $table) use ($hasAccountStatus): void {
            if (! $hasAccountStatus) {
                $table->string('account_status')->default('active')->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The original users migration contains these columns for new installations.
    }
};
