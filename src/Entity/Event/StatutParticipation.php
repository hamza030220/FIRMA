<?php

namespace App\Entity\Event;

enum StatutParticipation: string
{
    case EN_ATTENTE = 'en_attente';
    case CONFIRME   = 'confirme';
    case ANNULE     = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::CONFIRME   => 'Confirmé',
            self::ANNULE     => 'Annulé',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'badge-warning',
            self::CONFIRME   => 'badge-success',
            self::ANNULE     => 'badge-danger',
        };
    }
}
