<?php

namespace App\Entity\Event;

enum SecteurActivite: string
{
    case TECH       = 'tech';
    case FINANCE    = 'finance';
    case SANTE      = 'sante';
    case EDUCATION  = 'education';
    case INDUSTRIE  = 'industrie';
    case AUTRE      = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::TECH      => 'Technologie',
            self::FINANCE   => 'Finance',
            self::SANTE     => 'Santé',
            self::EDUCATION => 'Éducation',
            self::INDUSTRIE => 'Industrie',
            self::AUTRE     => 'Autre',
        };
    }
}
