<?php

namespace App\Services;

use App\Models\FeedbackDimension;
use App\Models\FeedbackItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FeedbackDimensionService
{
    /** @return Collection<int, FeedbackDimension> */
    public function all(): Collection
    {
        return FeedbackDimension::query()
            ->with(['items' => fn ($query) => $query->orderBy('position')->orderBy('id')])
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public function find(int $id): FeedbackDimension
    {
        return FeedbackDimension::query()->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): FeedbackDimension
    {
        $dimension = new FeedbackDimension;
        $dimension->forceFill([
            ...$this->validate($data),
            'position' => ((int) FeedbackDimension::query()->max('position')) + 1,
        ]);
        $dimension->save();

        return $dimension;
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): FeedbackDimension
    {
        $dimension = $this->find($id);
        $dimension->forceFill($this->validate($data));
        $dimension->save();

        return $dimension;
    }

    public function delete(int $id): bool
    {
        return (bool) $this->find($id)->delete();
    }

    public function findItem(int $dimensionId, int $itemId): FeedbackItem
    {
        return FeedbackItem::query()
            ->where('feedback_dimension_id', $dimensionId)
            ->findOrFail($itemId);
    }

    /** @param array<string, mixed> $data */
    public function createItem(int $dimensionId, array $data): FeedbackItem
    {
        $dimension = $this->find($dimensionId);

        $item = new FeedbackItem;
        $item->forceFill([
            'feedback_dimension_id' => $dimension->id,
            ...$this->validateItem($data),
            'position' => ((int) $dimension->items()->max('position')) + 1,
        ]);
        $item->save();

        return $item;
    }

    /** @param array<string, mixed> $data */
    public function updateItem(int $dimensionId, int $itemId, array $data): FeedbackItem
    {
        $item = $this->findItem($dimensionId, $itemId);
        $item->forceFill($this->validateItem($data));
        $item->save();

        return $item;
    }

    public function deleteItem(int $dimensionId, int $itemId): bool
    {
        return (bool) $this->findItem($dimensionId, $itemId)->delete();
    }

    /** @param list<int> $dimensionIds */
    public function reorder(array $dimensionIds): void
    {
        $dimensions = FeedbackDimension::query()->whereKey($dimensionIds)->get();

        if ($dimensions->count() !== FeedbackDimension::query()->count() || $dimensions->count() !== count($dimensionIds)) {
            throw ValidationException::withMessages(['dimension_ids' => 'The dimension order is invalid.']);
        }

        DB::transaction(function () use ($dimensionIds): void {
            foreach ($dimensionIds as $position => $dimensionId) {
                FeedbackDimension::query()->whereKey($dimensionId)->update(['position' => $position + 1]);
            }
        });
    }

    /** @param list<int> $itemIds */
    public function reorderItems(int $dimensionId, array $itemIds): void
    {
        $items = FeedbackItem::query()->where('feedback_dimension_id', $dimensionId)->whereKey($itemIds)->get();
        $itemCount = FeedbackItem::query()->where('feedback_dimension_id', $dimensionId)->count();

        if ($items->count() !== $itemCount || $items->count() !== count($itemIds)) {
            throw ValidationException::withMessages(['item_ids' => 'The item order is invalid.']);
        }

        DB::transaction(function () use ($itemIds): void {
            foreach ($itemIds as $position => $itemId) {
                FeedbackItem::query()->whereKey($itemId)->update(['position' => $position + 1]);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateItem(array $data): array
    {
        return Validator::make($data, [
            'description' => ['required', 'string'],
        ])->validate();
    }
}
