<?php

namespace App\Enums;

enum ClientType: string
{
    case Academe = 'academe';
    case Government = 'government';
    case PrivateCompanies = 'private-companies';
    case NonGovernmentOrganizations = 'non-government-organizations';
    case Individual = 'individual';

    public function label(): string
    {
        return match ($this) {
            self::Academe => 'Academe',
            self::Government => 'Government',
            self::PrivateCompanies => 'Private Companies',
            self::NonGovernmentOrganizations => 'Non Government Organizations',
            self::Individual => 'Individual',
        };
    }
}
