<?php

use App\Models\FeedbackRating;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private const DEFAULT_EMOJI_BY_VALUE = [
        '5' => '😄',
        '4' => '🙂',
        '3' => '😐',
        '2' => '🙁',
        '1' => '😞',
        'N/A' => '🚫',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('feedback_ratings', function (Blueprint $table): void {
            $table->string('emoji', 8)->nullable()->after('value');
        });

        FeedbackRating::query()->get(['id', 'value'])->each(function (FeedbackRating $rating): void {
            $rating->forceFill(['emoji' => self::DEFAULT_EMOJI_BY_VALUE[$rating->value] ?? '⭐'])->save();
        });

        Schema::table('feedback_ratings', function (Blueprint $table): void {
            $table->string('emoji', 8)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback_ratings', function (Blueprint $table): void {
            $table->dropColumn('emoji');
        });
    }
};
