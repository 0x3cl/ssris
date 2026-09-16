<?php

namespace App\Models;

use Database\Factories\FeedbackRatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'value'])]
class FeedbackRating extends Model
{
    /** @use HasFactory<FeedbackRatingFactory> */
    use HasFactory;
}
