<?php

namespace App\Models;

use Database\Factories\FeedbackItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable(['feedback_dimension_id', 'description', 'position'])]
class FeedbackItem extends Model implements Auditable
{
    /** @use HasFactory<FeedbackItemFactory> */
    use AuditableTrait, HasFactory;

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(FeedbackDimension::class, 'feedback_dimension_id');
    }
}
