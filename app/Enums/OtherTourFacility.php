<?php

namespace App\Enums;

enum OtherTourFacility: string
{
    case TelaGallery = 'tela-gallery';
    case TextileProductDevelopmentCenter = 'textile-product-development-center';
    case TechnologyBusinessIncubationCenter = 'technology-business-incubation-center';
    case NaturalFiberUtilizationSection = 'natural-fiber-utilization-section';

    public function label(): string
    {
        return match ($this) {
            self::TelaGallery => 'TELA Gallery, Textile Design and Innovation Hub',
            self::TextileProductDevelopmentCenter => 'Textile Product Development Center',
            self::TechnologyBusinessIncubationCenter => 'Technology Business Incubation Center',
            self::NaturalFiberUtilizationSection => 'Natural Fiber Utilization Section',
        };
    }
}
