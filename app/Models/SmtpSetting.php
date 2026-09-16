<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmtpSetting extends Model
{
    protected $guarded = [];

    protected $hidden = ['password'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['password' => 'encrypted'];
    }
}
