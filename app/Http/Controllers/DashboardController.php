<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'user' => [
                'name' => 'Carlos Martínez',
                'first_name' => 'Carlos',
                'department' => 'Recursos Humanos',
                'avatar' => asset('assets/figma/avatar.png'),
            ],
            'indicators' => [
                ['label' => 'Archivos privados', 'value' => '47', 'hint' => 'Acceso exclusivo tuyo', 'icon' => 'lock-keyhole'],
                ['label' => 'Archivos colaborativos', 'value' => '128', 'hint' => 'Compartidos con el equipo', 'icon' => 'users'],
                ['label' => 'Archivos públicos', 'value' => '34', 'hint' => 'Público general interno', 'icon' => 'globe'],
                ['label' => 'Espacio utilizado', 'value' => '12.4 GB', 'hint' => '12.4 GB / 50 GB utilizados', 'icon' => 'database'],
                ['label' => 'Papelera', 'value' => '8 elementos', 'hint' => 'Eliminado recientemente', 'icon' => 'trash'],
            ],
            'files' => [
                ['name' => 'Contrato_2025_Final.docx', 'visibility' => 'Privado', 'tone' => 'private', 'location' => 'Mis Archivos/Contratos', 'modified' => 'Hace 30 min', 'size' => '245 KB', 'icon' => 'file-text'],
                ['name' => 'Reporte_Mensual_Julio.xlsx', 'visibility' => 'Colaborativo', 'tone' => 'collaborative', 'location' => 'Departamento/Reportes', 'modified' => 'Hace 2 horas', 'size' => '1.8 MB', 'icon' => 'file-chart'],
                ['name' => 'Acta_Reunión_15Jul.pdf', 'visibility' => 'Colaborativo', 'tone' => 'collaborative', 'location' => 'Departamento/Actas', 'modified' => 'Ayer', 'size' => '520 KB', 'icon' => 'file-badge'],
                ['name' => 'Logo_Actualizado.png', 'visibility' => 'Público interno', 'tone' => 'public', 'location' => 'Público/Recursos', 'modified' => '20 Jul 2025', 'size' => '3.2 MB', 'icon' => 'file-image'],
                ['name' => 'Manual_Onboarding.docx', 'visibility' => 'Público interno', 'tone' => 'public', 'location' => 'Público/Manuales', 'modified' => '18 Jul 2025', 'size' => '1.1 MB', 'icon' => 'file-text'],
            ],
            'folders' => [
                ['name' => 'Contratos Activos', 'location' => 'Mis Archivos', 'time' => 'Hace 1 hora'],
                ['name' => 'Reportes Mensuales', 'location' => 'Departamento', 'time' => 'Hace 3 horas'],
                ['name' => 'Recursos Gráficos', 'location' => 'Público Interno', 'time' => 'Ayer'],
                ['name' => 'Expedientes 2025', 'location' => 'Departamento', 'time' => '19 Jul 2025'],
            ],
            'activities' => [
                ['text' => 'Subiste Contrato_2025_Final.docx', 'time' => 'Hace 30 min', 'icon' => 'arrow-up'],
                ['text' => 'Descargaste Reporte_Junio.xlsx', 'time' => 'Hace 1 hora', 'icon' => 'arrow-down'],
                ['text' => 'Creaste la carpeta Expedientes Q3', 'time' => 'Hace 3 horas', 'icon' => 'folder-plus'],
                ['text' => 'Moviste Nómina_Julio.xlsx a Privado', 'time' => 'Ayer', 'icon' => 'arrow-left-right'],
                ['text' => 'Eliminaste Borrador_Viejo.docx', 'time' => 'Ayer', 'icon' => 'trash'],
            ],
        ]);
    }
}
