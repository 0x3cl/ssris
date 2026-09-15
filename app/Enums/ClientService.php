<?php

namespace App\Enums;

enum ClientService: string
{
    case RddServices = 'rnd-services';
    case LabServices = 'lab-services';
    case ProcessingServices = 'processing-services';
    case TrainingServices = 'training-services';
    case PlantTourServices = 'plant-tour-services';
    case LibraryRegistration = 'library-registration';

    public function label(): string
    {
        return match ($this) {
            self::RddServices => 'R&D Services',
            self::LabServices => 'Lab Services',
            self::ProcessingServices => 'Processing Services',
            self::TrainingServices => 'Training Services',
            self::PlantTourServices => 'Plant Tour Services',
            self::LibraryRegistration => 'Library Registration',
        };
    }
}
