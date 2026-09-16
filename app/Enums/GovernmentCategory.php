<?php

namespace App\Enums;

enum GovernmentCategory: string
{
    case G2C = 'g2c';
    case G2B = 'g2b';
    case G2G = 'g2g';

    public function label(): string
    {
        return match ($this) {
            self::G2C => 'Government-to-Citizen (G2C)',
            self::G2B => 'Government-to-Business (G2B)',
            self::G2G => 'Government-to-Government (G2G)',
        };
    }

    public static function fromClientType(ClientType $type): self
    {
        return match ($type) {
            ClientType::Government => self::G2G,
            ClientType::Individual => self::G2C,
            ClientType::Academe, ClientType::PrivateCompanies, ClientType::NonGovernmentOrganizations => self::G2B,
        };
    }

    /** @return array<int, string> */
    public static function clientTypeValuesFor(self $category): array
    {
        return array_values(array_map(
            fn (ClientType $type): string => $type->value,
            array_filter(ClientType::cases(), fn (ClientType $type): bool => self::fromClientType($type) === $category),
        ));
    }
}
