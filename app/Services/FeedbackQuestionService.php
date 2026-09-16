<?php

namespace App\Services;

use App\Models\FeedbackQuestion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

class FeedbackQuestionService
{
    /** @return LengthAwarePaginator<int, FeedbackQuestion> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return FeedbackQuestion::query()->orderBy('id')->paginate(max(1, min($perPage, 100)));
    }

    public function find(int $id): FeedbackQuestion
    {
        return FeedbackQuestion::query()->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): FeedbackQuestion
    {
        $question = new FeedbackQuestion;
        $question->forceFill($this->validate($data));
        $question->save();

        return $question;
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): FeedbackQuestion
    {
        $question = $this->find($id);
        $question->forceFill($this->validate($data));
        $question->save();

        return $question;
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
        ])->validate();
    }
}
