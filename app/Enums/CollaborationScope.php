<?php

namespace App\Enums;

enum CollaborationScope: string
{
    case Department = 'department';
    case Selected = 'selected';

    public function label(): string
    {
        return match ($this) {
            self::Department => 'Todo mi departamento',
            self::Selected => 'Personas específicas',
        };
    }
}
