<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class DemoPermissionSeeder extends Seeder
{
    /**
     * Representative permissions for local demos only.
     *
     * @var array<string, string>
     */
    private const PERMISSIONS = [
        'nube_inicio_ver' => 'Acceder a Nube Empresarial',
        'nube_mis_archivos_ver' => 'Ver mis archivos',
        'nube_mis_archivos_crear_carpeta' => 'Crear carpetas privadas',
        'nube_mis_archivos_subir' => 'Subir archivos privados',
        'nube_mis_archivos_descargar' => 'Descargar archivos privados',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name => $displayName) {
            Permission::query()->updateOrCreate(
                ['name' => $name],
                ['display_name' => $displayName],
            );
        }
    }
}
