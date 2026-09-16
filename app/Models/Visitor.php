<?php

namespace App\Models;

use Database\Factories\VisitorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    /** @use HasFactory<VisitorFactory> */
    use HasFactory;

    protected $table = 'site_visitors_logs';

    protected $fillable = [
        'ip_address',
        'user_agent',
        'total_visits',
        'provider',
    ];

    protected $casts = [
        'total_visits' => 'integer',
    ];
}
