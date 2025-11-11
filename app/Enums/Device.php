<?php

namespace App\Enums;

enum Device: string
{
    case FCFA = 'FCFA';
    case EURO = 'EURO';
    case DOLLARS = 'DOLLARS';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::FCFA => 'Franc CFA',
            self::EURO => 'Euro',
            self::DOLLARS => 'Dollars US',
        };
    }

    public function symbol(): string
    {
        return match($this) {
            self::FCFA => 'FCFA',
            self::EURO => '€',
            self::DOLLARS => '$',
        };
    }
}