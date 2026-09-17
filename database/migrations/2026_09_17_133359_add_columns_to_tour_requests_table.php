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
        Schema::table('tour_requests', function (Blueprint $table) {
            $table->foreignId('service_request_id')
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
            $table->date('visit_date')->nullable();
            $table->time('visit_time')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->longText('message')->nullable();
            $table->integer('no_persons')->nullable();
            $table->integer('no_groups')->nullable();
            $table->longText('technology_assistance')->nullable();
            $table->longText('visit_objectives')->nullable();
            $table->string('prepared_by')->nullable();
            $table->string('noted_by')->nullable();
            $table->string('conforme_primary')->nullable();
            $table->string('conforme_secondary')->nullable();
            $table->string('conforme_optional')->nullable();
            $table->longText('remarks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tour_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_request_id');
            $table->dropColumn([
                'visit_date',
                'visit_time',
                'supervisor_name',
                'message',
                'no_persons',
                'no_groups',
                'technology_assistance',
                'visit_objectives',
                'prepared_by',
                'noted_by',
                'conforme_primary',
                'conforme_secondary',
                'conforme_optional',
                'remarks',
            ]);
        });
    }
};
