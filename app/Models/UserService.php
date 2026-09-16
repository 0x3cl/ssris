<?php

namespace App\Models;

use App\Enums\ClientService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'service'])]
class UserService extends Model
{
    protected $table = 'users_services';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'service' => ClientService::class,
        ];
    }
}
