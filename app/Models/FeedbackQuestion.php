<?php

namespace App\Models;

use Database\Factories\FeedbackQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable(['name'])]
class FeedbackQuestion extends Model implements Auditable
{
    /** @use HasFactory<FeedbackQuestionFactory> */
    use AuditableTrait, HasFactory;
}
