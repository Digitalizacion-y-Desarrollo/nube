<?php

namespace App\Enums;

enum FileVisibility: string
{
    case Private = 'private';
    case Collaborative = 'collaborative';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Privado',
            self::Collaborative => 'Colaborativo',
            self::Public => 'Público interno',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
