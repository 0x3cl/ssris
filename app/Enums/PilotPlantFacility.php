<?php

namespace App\Enums;

enum PilotPlantFacility: string
{
    case Spinning = 'spinning';
    case Weaving = 'weaving';
    case Powerloom = 'powerloom';
    case Knitting = 'knitting';
    case Finishing = 'finishing';
    case Handloom = 'handloom';

    public function label(): string
    {
        return match ($this) {
            self::Spinning => 'Spinning',
            self::Weaving => 'Weaving',
            self::Powerloom => 'Powerloom',
            self::Knitting => 'Knitting',
            self::Finishing => 'Finishing',
            self::Handloom => 'Handloom',
        };
    }
}
