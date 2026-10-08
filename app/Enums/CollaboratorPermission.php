<?php

namespace App\Enums;

enum CollaboratorPermission: string
{
    case View = 'view';
    case Download = 'download';
    case CreateFolder = 'create_folder';
    case Rename = 'rename';
    case Move = 'move';
    case Delete = 'delete';

    public function label(): string
    {
        return match ($this) {
            self::View => 'Ver',
            self::Download => 'Descargar',
            self::CreateFolder => 'Crear carpetas',
            self::Rename => 'Renombrar',
            self::Move => 'Mover',
            self::Delete => 'Eliminar',
        };
    }

    public function pivotColumn(): string
    {
        return "can_{$this->value}";
    }

    /**
     * @return list<string>
     */
    public static function defaults(): array
    {
        return [
            self::View->value,
            self::Download->value,
        ];
    }
}
