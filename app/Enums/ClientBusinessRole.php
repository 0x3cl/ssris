<?php

namespace App\Enums;

enum ClientBusinessRole: string
{
    case Manufacturer = 'manufacturer';
    case Trader = 'trader';
    case Exporter = 'exporter';

    public function label(): string
    {
        return match ($this) {
            self::Manufacturer => 'Manufacturer',
            self::Trader => 'Trader',
            self::Exporter => 'Exporter',
        };
    }
}
