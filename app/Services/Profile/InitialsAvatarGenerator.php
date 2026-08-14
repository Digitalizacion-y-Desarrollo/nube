<?php

namespace App\Services\Profile;

use Illuminate\Support\Str;

/**
 * Construye la foto de perfil predeterminada con las iniciales del usuario.
 *
 * Se devuelve como SVG embebido en un data URI para que funcione en las cuatro
 * ubicaciones donde ya se usa una etiqueta `img`, sin una petición adicional ni
 * dependencias externas. La política de seguridad de contenido ya admite
 * `data:` en `img-src`.
 */
class InitialsAvatarGenerator
{
    private const BACKGROUND = '#601633';

    private const FOREGROUND = '#FFFFFF';

    public static function dataUri(?string $name, ?string $lastName = null): string
    {
        $initials = self::initials($name, $lastName);
        $svg = self::svg($initials);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Toma la inicial del nombre y la del apellido. Si no hay apellido, usa las
     * dos primeras letras del nombre; si no hay nada utilizable, un guion.
     */
    public static function initials(?string $name, ?string $lastName = null): string
    {
        $name = self::clean($name);
        $lastName = self::clean($lastName);

        if ($name === '' && $lastName === '') {
            return '—';
        }

        if ($name === '') {
            return self::upper(mb_substr($lastName, 0, 1));
        }

        if ($lastName !== '') {
            return self::upper(mb_substr($name, 0, 1).mb_substr($lastName, 0, 1));
        }

        return self::upper(mb_substr($name, 0, 2));
    }

    private static function svg(string $initials): string
    {
        $text = htmlspecialchars($initials, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $background = self::BACKGROUND;
        $foreground = self::FOREGROUND;
        // Con una sola letra el trazo se ve pequeño; se compensa el tamaño.
        $fontSize = mb_strlen($initials) > 1 ? 40 : 48;

        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100">
            <rect width="100" height="100" rx="50" fill="{$background}"/>
            <text x="50" y="50" dy="0.35em" text-anchor="middle" fill="{$foreground}" font-family="Inter, 'Segoe UI', system-ui, sans-serif" font-size="{$fontSize}" font-weight="700" letter-spacing="1">{$text}</text>
            </svg>
            SVG;
    }

    private static function clean(?string $value): string
    {
        // Descarta signos y espacios para que la inicial sea siempre una letra.
        return trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', (string) $value) ?? '');
    }

    private static function upper(string $value): string
    {
        return Str::upper($value);
    }
}
