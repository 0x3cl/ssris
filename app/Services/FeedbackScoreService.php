<?php

namespace App\Services;

use App\Models\FeedbackRating;
use App\Models\FeedbackResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FeedbackScoreService
{
    /**
     * Summarize weighted feedback scores for the given (already filtered/scoped) query.
     *
     * @param  Builder<FeedbackResponse>  $responses
     * @return array{
     *     totalResponses: int,
     *     answeredRatings: int,
     *     overallScore: ?float,
     *     interpretation: ?string,
     *     perDimension: array<int, array{name: string, average: ?float, count: int}>,
     *     perCriterion: array<int, array{description: string, dimension: string, average: ?float, count: int}>,
     *     ratingDistribution: array<int, array{label: string, value: string, count: int}>,
     * }
     */
    public function summarize(Builder $responses): array
    {
        /** @var Collection<int, FeedbackResponse> $all */
        $all = $responses->get();

        $dimensionScores = [];
        $criterionScores = [];
        $ratingCounts = [];
        $weightedSum = 0.0;
        $weightedCount = 0;

        foreach ($all as $response) {
            foreach ($this->resolveAnswers($response) as $answer) {
                $ratingCounts[$answer['value']] ??= ['label' => $answer['ratingLabel'], 'value' => $answer['value'], 'count' => 0];
                $ratingCounts[$answer['value']]['count']++;

                if ($answer['weight'] === null) {
                    continue;
                }

                $weightedSum += $answer['weight'];
                $weightedCount++;

                $dimensionScores[$answer['dimension']] ??= ['sum' => 0.0, 'count' => 0];
                $dimensionScores[$answer['dimension']]['sum'] += $answer['weight'];
                $dimensionScores[$answer['dimension']]['count']++;

                $criterionScores[$answer['description']] ??= ['dimension' => $answer['dimension'], 'sum' => 0.0, 'count' => 0];
                $criterionScores[$answer['description']]['sum'] += $answer['weight'];
                $criterionScores[$answer['description']]['count']++;
            }
        }

        $overallScore = $weightedCount > 0 ? round($weightedSum / $weightedCount, 2) : null;

        return [
            'totalResponses' => $all->count(),
            'answeredRatings' => $weightedCount,
            'overallScore' => $overallScore,
            'interpretation' => $this->interpret($overallScore),
            'perDimension' => collect($dimensionScores)
                ->map(fn (array $score, string $name): array => [
                    'name' => $name,
                    'average' => $score['count'] > 0 ? round($score['sum'] / $score['count'], 2) : null,
                    'count' => $score['count'],
                ])
                ->values()
                ->all(),
            'perCriterion' => collect($criterionScores)
                ->map(fn (array $score, string $description): array => [
                    'description' => $description,
                    'dimension' => $score['dimension'],
                    'average' => $score['count'] > 0 ? round($score['sum'] / $score['count'], 2) : null,
                    'count' => $score['count'],
                ])
                ->values()
                ->all(),
            'ratingDistribution' => collect($ratingCounts)->values()->all(),
        ];
    }

    /** The weighted average score for a single response, or null if it has no scoreable answers. */
    public function scoreForResponse(FeedbackResponse $response): ?float
    {
        $answers = $this->resolveAnswers($response)->pluck('weight')->filter(fn (?float $weight): bool => $weight !== null);

        return $answers->isEmpty() ? null : round($answers->avg(), 2);
    }

    /**
     * Resolve each rating this response answered into its dimension, criterion description,
     * rating label, and numeric weight — read through the response's own frozen snapshot first,
     * so edits made later to the live survey never change how a historical response scores.
     *
     * @return Collection<int, array{dimension: string, description: string, value: string, ratingLabel: string, weight: ?float}>
     */
    private function resolveAnswers(FeedbackResponse $response): Collection
    {
        $snapshotRatings = collect($response->snapshot['ratings'] ?? [])->keyBy('value');
        $liveRatings = FeedbackRating::query()->get(['name', 'value', 'weight'])->keyBy('value');

        $itemLookup = collect($response->snapshot['dimensions'] ?? [])
            ->flatMap(fn (array $dimension): array => collect($dimension['items'] ?? [])
                ->map(fn (array $item): array => [
                    'id' => $item['id'],
                    'description' => $item['description'],
                    'dimension' => $dimension['name'],
                ])
                ->all())
            ->keyBy('id');

        return collect($response->ratings ?? [])
            ->map(function ($value, $itemId) use ($itemLookup, $snapshotRatings, $liveRatings): ?array {
                $item = $itemLookup->get((int) $itemId);

                if ($item === null) {
                    return null;
                }

                $value = (string) $value;

                return [
                    'dimension' => $item['dimension'],
                    'description' => $item['description'],
                    'value' => $value,
                    'ratingLabel' => $snapshotRatings->get($value)['name'] ?? $liveRatings->get($value)?->name ?? $value,
                    'weight' => $this->resolveWeight($snapshotRatings, $liveRatings, $value),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $snapshotRatings
     * @param  Collection<string, FeedbackRating>  $liveRatings
     */
    private function resolveWeight(Collection $snapshotRatings, Collection $liveRatings, string $value): ?float
    {
        $snapshotWeight = $snapshotRatings->get($value)['weight'] ?? null;

        if ($snapshotWeight !== null) {
            return (float) $snapshotWeight;
        }

        $liveWeight = $liveRatings->get($value)?->weight;

        if ($liveWeight !== null) {
            return (float) $liveWeight;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function interpret(?float $score): ?string
    {
        return match (true) {
            $score === null => null,
            $score >= 4.5 => 'Excellent',
            $score >= 3.5 => 'Very Satisfactory',
            $score >= 2.5 => 'Satisfactory',
            $score >= 1.5 => 'Fair',
            default => 'Poor',
        };
    }
}
