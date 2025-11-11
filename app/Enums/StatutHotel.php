<?php

namespace App\Enums;

enum StatutHotel: string
{
    case ACTIF = 'actif';
    case INACTIF = 'inactif';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::ACTIF => 'Actif',
            self::INACTIF => 'Inactif',
        };
    }
}