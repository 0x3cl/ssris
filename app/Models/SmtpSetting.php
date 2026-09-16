<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class SmtpSetting extends Model implements Auditable
{
    use AuditableTrait;

    protected $guarded = [];

    protected $hidden = ['password'];

    /** @var array<int, string> */
    protected $auditExclude = ['password'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['password' => 'encrypted'];
    }
}
