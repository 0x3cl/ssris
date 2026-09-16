<?php

namespace App\Models;

use Database\Factories\FeedbackQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class FeedbackQuestion extends Model
{
    /** @use HasFactory<FeedbackQuestionFactory> */
    use HasFactory;
}
