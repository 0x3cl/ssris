<?php

namespace App\Enums;

enum ClientEnterpriseSize: string
{
    case Micro = 'micro';
    case Small = 'small';
    case Medium = 'medium';
    case LargeCompanies = 'large-companies';

    public function label(): string
    {
        return match ($this) {
            self::Micro => 'Micro (less than P 1,500,001 Total Assets)',
            self::Small => 'Small (P 1,500,001 - P 15,000,000)',
            self::Medium => 'Medium (P 15,000,001 - P 60,000,000)',
            self::LargeCompanies => 'Large Companies',
        };
    }
}
