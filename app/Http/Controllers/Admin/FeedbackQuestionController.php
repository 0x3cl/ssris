<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedbackQuestion;
use App\Services\FeedbackQuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackQuestionController extends Controller
{
    public function __construct(private readonly FeedbackQuestionService $questions) {}

    public function index(Request $request): Response
    {
        $entries = (int) $request->integer('entries', 10);
        $entries = in_array($entries, [10, 25, 50], true) ? $entries : 10;

        return Inertia::render('admin/feedback-questions', [
            'filters' => compact('entries'),
            'questions' => $this->questions->paginate($entries)->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/feedback-question-form', ['question' => null]);
    }

    public function edit(FeedbackQuestion $feedbackQuestion): Response
    {
        return Inertia::render('admin/feedback-question-form', [
            'question' => ['id' => $feedbackQuestion->id, 'name' => $feedbackQuestion->name],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->questions->create($request->all());

        return to_route('admin.feedback-builder.questions.index')->with('success', 'Question added.');
    }

    public function update(Request $request, FeedbackQuestion $feedbackQuestion): RedirectResponse
    {
        $this->questions->update($feedbackQuestion->id, $request->all());

        return to_route('admin.feedback-builder.questions.index')->with('success', 'Question updated.');
    }

    public function destroy(Request $request, FeedbackQuestion $feedbackQuestion): RedirectResponse
    {
        $this->validateDeleteChallenge($request);
        $this->questions->delete($feedbackQuestion->id);

        return back()->with('success', 'Question deleted.');
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
