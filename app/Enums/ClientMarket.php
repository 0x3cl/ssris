<?php

namespace App\Enums;

enum ClientMarket: string
{
    case Local = 'local';
    case International = 'international';
    case LocalAndInternational = 'local-and-international';

    public function label(): string
    {
        return match ($this) {
            self::Local => 'Local',
            self::International => 'International',
            self::LocalAndInternational => 'Local & International',
        };
    }
}
