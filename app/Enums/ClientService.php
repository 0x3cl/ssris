<?php

namespace App\Enums;

enum ClientService: string
{
    case RddServices = 'rdd-services';
    case TsdLabServices = 'tsd-lab-services';
    case TsdIcytProcessingServices = 'tsd-icyt-processing-services';
    case TipsTrainingServices = 'tips-training-services';
    case TipsPlantTourServices = 'tips-plant-tour-services';
    case PictsLibraryRegistration = 'picts-library-registration';

    public function label(): string
    {
        return match ($this) {
            self::RddServices => 'RDD Services',
            self::TsdLabServices => 'TSD Lab Services',
            self::TsdIcytProcessingServices => 'TSD ICYT Processing Services',
            self::TipsTrainingServices => 'TIPS Training Services',
            self::TipsPlantTourServices => 'TIPS Plant Tour Services',
            self::PictsLibraryRegistration => 'PICTS Library Registration',
        };
    }
}
