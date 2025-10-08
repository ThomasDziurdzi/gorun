<?php

namespace App\Enum;

enum RunningLevel: string
{
    case BEGINNER = 'BEGINNER';
    case INTERMEDIATE = 'INTERMEDIATE';
    case ADVANCED = 'ADVANCED';
    case ALL_LEVELS = 'ALL_LEVELS';

    public function label(): string
    {
        return match ($this) {
            self::BEGINNER => 'Débutant',
            self::INTERMEDIATE => 'Intermédiaire',
            self::ADVANCED => 'Confirmé',
            self::ALL_LEVELS => 'Tous niveaux',
        };
    }
}
