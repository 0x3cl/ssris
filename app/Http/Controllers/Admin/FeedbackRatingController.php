<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedbackRating;
use App\Services\FeedbackRatingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackRatingController extends Controller
{
    public function __construct(private readonly FeedbackRatingService $ratings) {}

    public function index(Request $request): Response
    {
        $entries = (int) $request->integer('entries', 10);
        $entries = in_array($entries, [10, 25, 50], true) ? $entries : 10;

        return Inertia::render('admin/feedback-ratings', [
            'filters' => compact('entries'),
            'ratings' => $this->ratings->paginate($entries)->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/feedback-rating-form', ['rating' => null]);
    }

    public function edit(FeedbackRating $feedbackRating): Response
    {
        return Inertia::render('admin/feedback-rating-form', [
            'rating' => ['id' => $feedbackRating->id, 'name' => $feedbackRating->name, 'value' => $feedbackRating->value, 'weight' => $feedbackRating->weight],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ratings->create($request->all());

        return to_route('admin.feedback-builder.ratings.index')->with('success', 'Rating added.');
    }

    public function update(Request $request, FeedbackRating $feedbackRating): RedirectResponse
    {
        $this->ratings->update($feedbackRating->id, $request->all());

        return to_route('admin.feedback-builder.ratings.index')->with('success', 'Rating updated.');
    }

    public function destroy(Request $request, FeedbackRating $feedbackRating): RedirectResponse
    {
        $this->validateDeleteChallenge($request);
        $this->ratings->delete($feedbackRating->id);

        return back()->with('success', 'Rating deleted.');
    }

    private function validateDeleteChallenge(Request $request): void
    {
        $data = $request->validate(['delete_code' => ['required', 'digits:4']]);
        $challenge = $request->session()->get('admin.delete_challenge');

        if (! is_string($challenge) || ! hash_equals($challenge, $data['delete_code'])) {
            throw ValidationException::withMessages(['delete_code' => 'Enter the displayed four-digit confirmation code to delete this record.']);
        }

        $request->session()->forget('admin.delete_challenge');
    }
}
