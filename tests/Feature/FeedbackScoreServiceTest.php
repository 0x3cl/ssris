<?php

namespace Tests\Feature;

use App\Models\FeedbackLink;
use App\Models\FeedbackRating;
use App\Models\FeedbackResponse;
use App\Services\FeedbackScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_summarize_averages_weights_and_excludes_not_applicable_answers(): void
    {
        FeedbackRating::factory()->create(['value' => '5', 'name' => 'Excellent', 'weight' => 5]);
        FeedbackRating::factory()->create(['value' => '3', 'name' => 'Satisfactory', 'weight' => 3]);

        $snapshot = [
            'dimensions' => [
                ['id' => 1, 'name' => 'Responsiveness', 'items' => [
                    ['id' => 10, 'description' => 'Prompt response'],
                    ['id' => 11, 'description' => 'Courteous staff'],
                ]],
            ],
            'ratings' => [
                ['id' => 1, 'name' => 'Excellent', 'value' => '5', 'weight' => 5],
                ['id' => 2, 'name' => 'Satisfactory', 'value' => '3', 'weight' => 3],
                ['id' => 3, 'name' => 'Not Applicable', 'value' => 'N/A', 'weight' => null],
            ],
        ];

        FeedbackResponse::factory()->create([
            'ratings' => ['10' => '5', '11' => '3'],
            'snapshot' => $snapshot,
        ]);
        FeedbackResponse::factory()->create([
            'ratings' => ['10' => '5', '11' => 'N/A'],
            'snapshot' => $snapshot,
        ]);

        $summary = app(FeedbackScoreService::class)->summarize(FeedbackResponse::query());

        $this->assertSame(2, $summary['totalResponses']);
        $this->assertSame(3, $summary['answeredRatings']);
        $this->assertEqualsWithDelta(4.33, $summary['overallScore'], 0.01);
        $this->assertSame('Very Satisfactory', $summary['interpretation']);

        $promptResponse = collect($summary['perCriterion'])->firstWhere('description', 'Prompt response');
        $this->assertSame(5.0, $promptResponse['average']);
        $this->assertSame(2, $promptResponse['count']);

        $courteousResponse = collect($summary['perCriterion'])->firstWhere('description', 'Courteous staff');
        $this->assertSame(3.0, $courteousResponse['average']);
        $this->assertSame(1, $courteousResponse['count'], 'The N/A answer must not count toward the average.');
    }

    public function test_summarize_falls_back_to_the_live_rating_table_when_the_snapshot_has_no_weight(): void
    {
        FeedbackRating::factory()->create(['value' => '4', 'name' => 'Very Satisfactory', 'weight' => 4]);

        $legacySnapshot = [
            'dimensions' => [
                ['id' => 1, 'name' => 'Reliability', 'items' => [
                    ['id' => 20, 'description' => 'Timeliness'],
                ]],
            ],
            // Historical snapshots recorded before the weight column existed have no 'weight' key.
            'ratings' => [
                ['id' => 1, 'name' => 'Very Satisfactory', 'value' => '4'],
            ],
        ];

        FeedbackResponse::factory()->create([
            'ratings' => ['20' => '4'],
            'snapshot' => $legacySnapshot,
        ]);

        $summary = app(FeedbackScoreService::class)->summarize(FeedbackResponse::query());

        $this->assertSame(4.0, $summary['overallScore']);
    }

    public function test_score_for_response_returns_null_when_every_answer_is_not_applicable(): void
    {
        FeedbackRating::factory()->create(['value' => 'N/A', 'name' => 'Not Applicable', 'weight' => null]);

        $response = FeedbackResponse::factory()->create([
            'ratings' => ['30' => 'N/A'],
            'snapshot' => [
                'dimensions' => [
                    ['id' => 1, 'name' => 'Outcome', 'items' => [
                        ['id' => 30, 'description' => 'Overall satisfaction'],
                    ]],
                ],
                'ratings' => [
                    ['id' => 1, 'name' => 'Not Applicable', 'value' => 'N/A', 'weight' => null],
                ],
            ],
        ]);

        $this->assertNull(app(FeedbackScoreService::class)->scoreForResponse($response));
    }

    public function test_feedback_link(): void
    {
        // Guard against FeedbackResponse's factory default (an empty snapshot/ratings)
        // silently producing a "score" for a response that answered nothing.
        $link = FeedbackLink::factory()->create();
        $response = FeedbackResponse::factory()->create(['feedback_link_id' => $link->id]);

        $this->assertNull(app(FeedbackScoreService::class)->scoreForResponse($response));
    }
}
