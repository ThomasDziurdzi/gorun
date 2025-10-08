<?php

namespace App\Enum;

enum EventStatus: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case CANCELLED = 'CANCELLED';
    case COMPLETED = 'COMPLETED';
    
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PUBLISHED => 'Publié',
            self::CANCELLED => 'Annulé',
            self::COMPLETED => 'Terminé',
        };
    }
}