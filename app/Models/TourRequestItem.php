<?php

namespace App\Models;

use Database\Factories\TourRequestItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable(['tour_request_id', 'testing_lab', 'pilot_plant', 'others'])]
class TourRequestItem extends Model implements Auditable
{
    /** @use HasFactory<TourRequestItemFactory> */
    use AuditableTrait, HasFactory;

    public function tourRequest(): BelongsTo
    {
        return $this->belongsTo(TourRequest::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'testing_lab' => 'array',
            'pilot_plant' => 'array',
            'others' => 'array',
        ];
    }
}
