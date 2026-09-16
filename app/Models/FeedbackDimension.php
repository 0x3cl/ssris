<?php

namespace App\Models;

use Database\Factories\FeedbackDimensionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable(['name', 'position'])]
class FeedbackDimension extends Model implements Auditable
{
    /** @use HasFactory<FeedbackDimensionFactory> */
    use AuditableTrait, HasFactory;

    public function items(): HasMany
    {
        return $this->hasMany(FeedbackItem::class);
    }
}
