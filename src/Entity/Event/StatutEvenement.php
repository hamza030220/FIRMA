<?php

namespace App\Entity\Event;

enum StatutEvenement: string
{
    case ACTIF   = 'actif';
    case ANNULE  = 'annule';
    case TERMINE = 'termine';
    case COMPLET = 'complet';

    public function label(): string
    {
        return match ($this) {
            self::ACTIF   => 'Actif',
            self::ANNULE  => 'Annulé',
            self::TERMINE => 'Terminé',
            self::COMPLET => 'Complet',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIF   => 'badge-success',
            self::ANNULE  => 'badge-danger',
            self::TERMINE => 'badge-secondary',
            self::COMPLET => 'badge-warning',
        };
    }
}
