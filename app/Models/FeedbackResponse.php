<?php

namespace App\Models;

use Database\Factories\FeedbackResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable(['feedback_link_id', 'ratings', 'answers', 'snapshot'])]
class FeedbackResponse extends Model implements Auditable
{
    /** @use HasFactory<FeedbackResponseFactory> */
    use AuditableTrait, HasFactory;

    public function link(): BelongsTo
    {
        return $this->belongsTo(FeedbackLink::class, 'feedback_link_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ratings' => 'array',
            'answers' => 'array',
            'snapshot' => 'array',
        ];
    }
}
