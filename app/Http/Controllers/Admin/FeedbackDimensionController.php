<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedbackDimension;
use App\Models\FeedbackItem;
use App\Models\FeedbackQuestion;
use App\Models\FeedbackRating;
use App\Services\FeedbackDimensionService;
use App\Services\FeedbackPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackDimensionController extends Controller
{
    public function __construct(
        private readonly FeedbackDimensionService $dimensions,
        private readonly FeedbackPdfService $feedbackPdf,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/feedback-builder', [
            'dimensions' => $this->dimensions->all()->map(fn (FeedbackDimension $dimension): array => [
                'id' => $dimension->id,
                'name' => $dimension->name,
                'position' => $dimension->position,
                'items' => $dimension->items->map(fn (FeedbackItem $item): array => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'position' => $item->position,
                ]),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/feedback-dimension-form', ['dimension' => null]);
    }

    public function visualize(): Response
    {
        return Inertia::render('admin/feedback-visualization', [
            'dimensions' => $this->dimensionPayloads(),
            'questions' => FeedbackQuestion::query()->orderBy('id')->get(['id', 'name']),
            'ratings' => FeedbackRating::query()->orderBy('id')->get(['id', 'name', 'value']),
        ]);
    }

    public function downloadPdf(): HttpResponse
    {
        $filename = 'customer-satisfaction-feedback.pdf';

        return response($this->feedbackPdf->render(
            $this->dimensionPayloads(),
            $this->questionPayloads(),
            $this->ratingPayloads(),
        ), 200, [
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function edit(FeedbackDimension $feedbackDimension): Response
    {
        return Inertia::render('admin/feedback-dimension-form', [
            'dimension' => ['id' => $feedbackDimension->id, 'name' => $feedbackDimension->name],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->dimensions->create($request->all());

        return to_route('admin.feedback-builder.index')->with('success', 'Dimension added.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dimension_ids' => ['required', 'array', 'min:1'],
            'dimension_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $this->dimensions->reorder($data['dimension_ids']);

        return back()->with('success', 'Dimension order updated.');
    }

    public function update(Request $request, FeedbackDimension $feedbackDimension): RedirectResponse
    {
        $this->dimensions->update($feedbackDimension->id, $request->all());

        return to_route('admin.feedback-builder.index')->with('success', 'Dimension updated.');
    }

    public function destroy(Request $request, FeedbackDimension $feedbackDimension): RedirectResponse
    {
        $this->validateDeleteChallenge($request);
        $this->dimensions->delete($feedbackDimension->id);

        return back()->with('success', 'Dimension deleted.');
    }

    public function storeItem(Request $request, FeedbackDimension $feedbackDimension): RedirectResponse
    {
        $this->dimensions->createItem($feedbackDimension->id, $request->all());

        return back()->with('success', 'Item added.');
    }

    public function reorderItems(Request $request, FeedbackDimension $feedbackDimension): RedirectResponse
    {
        $data = $request->validate([
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $this->dimensions->reorderItems($feedbackDimension->id, $data['item_ids']);

        return back()->with('success', 'Description order updated.');
    }

    public function updateItem(Request $request, FeedbackDimension $feedbackDimension, FeedbackItem $feedbackItem): RedirectResponse
    {
        $this->dimensions->updateItem($feedbackDimension->id, $feedbackItem->id, $request->all());

        return back()->with('success', 'Item updated.');
    }

    public function destroyItem(Request $request, FeedbackDimension $feedbackDimension, FeedbackItem $feedbackItem): RedirectResponse
    {
        $this->validateDeleteChallenge($request);
        $this->dimensions->deleteItem($feedbackDimension->id, $feedbackItem->id);

        return back()->with('success', 'Item deleted.');
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

    /** @return array<int, array<string, mixed>> */
    private function dimensionPayloads(): array
    {
        return $this->dimensions->all()->map(fn (FeedbackDimension $dimension): array => [
            'id' => $dimension->id,
            'name' => $dimension->name,
            'position' => $dimension->position,
            'items' => $dimension->items->map(fn (FeedbackItem $item): array => [
                'id' => $item->id,
                'description' => $item->description,
                'position' => $item->position,
            ])->all(),
        ])->all();
    }

    /** @return array<int, array{id: int, name: string}> */
    private function questionPayloads(): array
    {
        return FeedbackQuestion::query()
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (FeedbackQuestion $question): array => ['id' => $question->id, 'name' => $question->name])
            ->all();
    }

    /** @return array<int, array{id: int, name: string, value: string|int}> */
    private function ratingPayloads(): array
    {
        return FeedbackRating::query()
            ->orderBy('id')
            ->get(['id', 'name', 'value'])
            ->map(fn (FeedbackRating $rating): array => ['id' => $rating->id, 'name' => $rating->name, 'value' => $rating->value])
            ->all();
    }
}
