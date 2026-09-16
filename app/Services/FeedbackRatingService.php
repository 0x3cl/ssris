<?php

namespace App\Services;

use App\Models\FeedbackRating;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

class FeedbackRatingService
{
    /** @return LengthAwarePaginator<int, FeedbackRating> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return FeedbackRating::query()->orderBy('id')->paginate(max(1, min($perPage, 100)));
    }

    public function find(int $id): FeedbackRating
    {
        return FeedbackRating::query()->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): FeedbackRating
    {
        $rating = new FeedbackRating;
        $rating->forceFill($this->validate($data));
        $rating->save();

        return $rating;
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): FeedbackRating
    {
        $rating = $this->find($id);
        $rating->forceFill($this->validate($data));
        $rating->save();

        return $rating;
    }

    public function delete(int $id): bool
    {
        return (bool) $this->find($id)->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:255'],
        ])->validate();
    }
}
