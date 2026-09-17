<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackDisplaySetting extends Model
{
    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'show_emoji' => 'boolean',
        ];
    }

    public static function showEmoji(): bool
    {
        return (bool) static::query()->value('show_emoji');
    }
}
