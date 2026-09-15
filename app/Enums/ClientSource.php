<?php

namespace App\Enums;

enum ClientSource: string
{
    case PtriWebsite = 'ptri-website';
    case Internet = 'internet';
    case NewspaperMagazine = 'newspaper-magazine';
    case Referral = 'referral';

    public function label(): string
    {
        return match ($this) {
            self::PtriWebsite => 'PTRI Website',
            self::Internet => 'Internet',
            self::NewspaperMagazine => 'Newspaper/Magazine',
            self::Referral => 'Referral',
        };
    }
}
