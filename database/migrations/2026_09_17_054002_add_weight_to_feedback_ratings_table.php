<?php

use App\Models\FeedbackRating;
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
        Schema::table('feedback_ratings', function (Blueprint $table): void {
            $table->decimal('weight', 5, 2)->nullable()->after('value');
        });

        FeedbackRating::query()->get(['id', 'value'])->each(function (FeedbackRating $rating): void {
            $rating->forceFill([
                'weight' => is_numeric($rating->value) ? (float) $rating->value : null,
            ])->save();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback_ratings', function (Blueprint $table): void {
            $table->dropColumn('weight');
        });
    }
};
