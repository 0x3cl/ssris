<?php

namespace App\Enums;

enum TestingLabFacility: string
{
    case Physical = 'physical';
    case Chemical = 'chemical';

    public function label(): string
    {
        return match ($this) {
            self::Physical => 'Physical',
            self::Chemical => 'Chemical',
        };
    }
}
