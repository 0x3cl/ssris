<?php

namespace App\Models;

use Database\Factories\FeedbackRatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable(['name', 'value'])]
class FeedbackRating extends Model implements Auditable
{
    /** @use HasFactory<FeedbackRatingFactory> */
    use AuditableTrait, HasFactory;
}
