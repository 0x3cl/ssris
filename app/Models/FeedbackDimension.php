<?php

namespace App\Models;

use Database\Factories\FeedbackDimensionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'position'])]
class FeedbackDimension extends Model
{
    /** @use HasFactory<FeedbackDimensionFactory> */
    use HasFactory;

    public function items(): HasMany
    {
        return $this->hasMany(FeedbackItem::class);
    }
}
