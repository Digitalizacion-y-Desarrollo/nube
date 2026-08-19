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
        'nube_inicio_ver' => 'Acceder a Nube Municipal',
        'nube_mis_archivos_ver' => 'Ver mis archivos',
        'nube_archivos_crear_carpeta' => 'Crear carpetas privadas',
        'nube_mis_archivos_renombrar' => 'Renombrar carpetas privadas',
        'nube.archivos.eliminar' => 'Eliminar carpetas y archivos privados',
        'nube.archivos.subir' => 'Subir archivos privados',
        'nube.archivos.descargar' => 'Descargar archivos privados',
        'nube_mis_archivos_mover' => 'Mover archivos privados',
        'nube.archivos.publicar' => 'Cambiar visibilidad de archivos privados',
        'nube_papelera_restaurar' => 'Restaurar archivos eliminados',
        'nube_departamento_ver' => 'Ver archivos del departamento',
        'nube_departamento_crear_carpeta' => 'Crear carpetas del departamento',
        'nube_departamento_subir' => 'Subir archivos del departamento',
        'nube_departamento_descargar' => 'Descargar archivos del departamento',
        'nube_departamento_renombrar' => 'Renombrar archivos del departamento',
        'nube_departamento_mover' => 'Mover archivos del departamento',
        'nube_departamento_eliminar' => 'Eliminar archivos del departamento',
        'nube_departamento_publicar' => 'Cambiar visibilidad de archivos del departamento',
        'nube_publicos_ver' => 'Ver archivos públicos internos',
        'nube_publicos_crear_carpeta' => 'Crear carpetas públicas internas',
        'nube_publicos_subir' => 'Subir archivos públicos internos',
        'nube_publicos_descargar' => 'Descargar archivos públicos internos',
        'nube_publicos_renombrar' => 'Renombrar archivos públicos internos',
        'nube_publicos_mover' => 'Mover archivos públicos internos',
        'nube_publicos_eliminar' => 'Eliminar archivos públicos internos',
        'nube_publicos_publicar' => 'Cambiar visibilidad de archivos públicos internos',
        'nube_papelera_ver' => 'Ver la papelera',
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
