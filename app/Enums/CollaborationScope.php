<?php

namespace App\Enums;

enum CollaborationScope: string
{
    case Department = 'department';
    case Area = 'area';
    case Selected = 'selected';

    public function label(): string
    {
        return match ($this) {
            self::Department => 'Todo mi departamento',
            self::Area => 'Toda mi área',
            self::Selected => 'Personas específicas',
        };
    }
}
