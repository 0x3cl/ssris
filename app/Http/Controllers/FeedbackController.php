<?php

namespace App\Http\Controllers;

use App\Enums\ServiceRequestLogAction;
use App\Enums\ServiceRequestStatus;
use App\Models\FeedbackDimension;
use App\Models\FeedbackItem;
use App\Models\FeedbackLink;
use App\Models\FeedbackQuestion;
use App\Models\FeedbackRating;
use App\Models\FeedbackResponse;
use App\Services\FeedbackDimensionService;
use App\Services\ServiceRequestLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackDimensionService $dimensions,
        private readonly ServiceRequestLogger $serviceRequestLogger,
    ) {}

    public function show(string $token): Response
    {
        $link = FeedbackLink::query()->where('token', $token)->first();

        if ($link === null || $link->isExpired() || $link->isSubmitted()) {
            return $this->unavailable($link);
        }

        $link->load('serviceRequest.client');
        $serviceRequest = $link->serviceRequest;
        $client = $serviceRequest->client;
        $snapshot = $this->surveySnapshot();

        return Inertia::render('feedback-form', [
            'token' => $token,
            'isLocal' => app()->environment('local'),
            'client' => [
                'fullname' => $client->fullname,
                'email' => $client->email,
                'mobile_no' => $client->mobile_no,
                'address' => trim("{$client->address}, {$client->municipality}, {$client->province}, {$client->region}", ', '),
                'company_or_school' => $client->company ?? $client->school_name,
                'type_client' => $client->type_client->label(),
                'service' => $serviceRequest->service->label(),
            ],
            'dimensions' => $snapshot['dimensions'],
            'ratings' => $snapshot['ratings'],
            'questions' => $snapshot['questions'],
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse|Response
    {
        $link = FeedbackLink::query()->where('token', $token)->first();

        if ($link === null || $link->isExpired() || $link->isSubmitted()) {
            return $this->unavailable($link);
        }

        // Snapshot the survey as it stands right now, so this response stays
        // accurate and readable even if dimensions/items/ratings/questions
        // are later edited or deleted in the Feedback Builder.
        $snapshot = $this->surveySnapshot();
        $itemIds = collect($snapshot['dimensions'])->pluck('items')->flatten(1)->pluck('id')->all();
        $ratingValues = collect($snapshot['ratings'])->pluck('value')->all();
        $questionIds = collect($snapshot['questions'])->pluck('id')->all();

        $data = $request->validate([
            'ratings' => ['required', 'array'],
            'ratings.*' => [Rule::in($ratingValues)],
            'answers' => ['sometimes', 'array'],
            'answers.*' => ['nullable', 'string'],
        ]);

        foreach (array_keys($data['ratings']) as $itemId) {
            if (! in_array((int) $itemId, $itemIds, true)) {
                abort(422, 'One of the rated items is no longer part of this survey.');
            }
        }

        foreach (array_keys($data['answers'] ?? []) as $questionId) {
            if (! in_array((int) $questionId, $questionIds, true)) {
                abort(422, 'One of the answered questions is no longer part of this survey.');
            }
        }

        FeedbackResponse::create([
            'feedback_link_id' => $link->id,
            'ratings' => $data['ratings'],
            'answers' => $data['answers'] ?? [],
            'snapshot' => $snapshot,
        ]);

        $link->update(['submitted_at' => now()]);
        $link->serviceRequest->update(['status' => ServiceRequestStatus::Completed]);

        $this->serviceRequestLogger->log(
            $link->serviceRequest,
            ServiceRequestLogAction::FeedbackSubmitted,
            'Customer satisfaction feedback submitted by the client. The request has been marked as completed.',
            $link->serviceRequest->client->fullname,
        );

        return to_route('feedback.show', $token);
    }

    /**
     * The full survey structure exactly as it stands right now: dimensions with
     * their items, the rating scale, and the open-ended questions. Used both to
     * render the form and, on submit, to freeze what the client actually saw.
     *
     * @return array{dimensions: array<int, array<string, mixed>>, ratings: array<int, array<string, mixed>>, questions: array<int, array<string, mixed>>}
     */
    private function surveySnapshot(): array
    {
        return [
            'dimensions' => $this->dimensions->all()->map(fn (FeedbackDimension $dimension): array => [
                'id' => $dimension->id,
                'name' => $dimension->name,
                'items' => $dimension->items->map(fn (FeedbackItem $item): array => [
                    'id' => $item->id,
                    'description' => $item->description,
                ])->all(),
            ])->all(),
            'ratings' => FeedbackRating::query()->orderBy('id')->get(['id', 'name', 'value'])->toArray(),
            'questions' => FeedbackQuestion::query()->orderBy('id')->get(['id', 'name'])->toArray(),
        ];
    }

    private function unavailable(?FeedbackLink $link): Response
    {
        return Inertia::render('feedback-unavailable', [
            'reason' => match (true) {
                $link === null => 'not-found',
                $link->isSubmitted() => 'submitted',
                default => 'expired',
            },
        ]);
    }
}
