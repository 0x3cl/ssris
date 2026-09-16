<?php

namespace App\Models;

use Database\Factories\FeedbackItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['feedback_dimension_id', 'description', 'position'])]
class FeedbackItem extends Model
{
    /** @use HasFactory<FeedbackItemFactory> */
    use HasFactory;

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(FeedbackDimension::class, 'feedback_dimension_id');
    }
}
