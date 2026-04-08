<?php

namespace App\Entity\Event;

enum TypeEvenement: string
{
    case EXPOSITION  = 'exposition';
    case ATELIER     = 'atelier';
    case CONFERENCE  = 'conference';
    case SALON       = 'salon';
    case FORMATION   = 'formation';
    case AUTRE       = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::EXPOSITION => 'Exposition',
            self::ATELIER    => 'Atelier',
            self::CONFERENCE => 'Conférence',
            self::SALON      => 'Salon',
            self::FORMATION  => 'Formation',
            self::AUTRE      => 'Autre',
        };
    }
}
