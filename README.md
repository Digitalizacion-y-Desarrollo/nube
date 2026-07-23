# Nube Municipal

MVP de una plataforma interna para almacenar, organizar y administrar archivos
privados, colaborativos y públicos internos.

## Requisitos

- PHP 8.2.
- Composer 2.
- Node.js y npm.
- MySQL 8.

En el entorno Wamp utilizado para el proyecto, PHP 8.2 está disponible en:

```text
C:\wamp64\bin\php\php8.2.29\php.exe
```

## Instalación

```powershell
C:\wamp64\bin\php\php8.2.29\php.exe C:\ProgramData\ComposerSetup\bin\composer.phar install
npm install
npm run build
C:\wamp64\bin\php\php8.2.29\php.exe artisan key:generate
```

La base local configurada es `nube_municipal`.

## Rutas actuales

```text
GET  /       Dashboard demostrativo
GET  /login  Inicio de sesión
POST /login  Validación inicial del formulario
GET  /up     Estado de Laravel
```

La autenticación real con el sistema central se implementará en el Épico 3.

## Arquitectura inicial

```text
app/
├── Enums/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Policies/
└── Services/

resources/views/
├── auth/
├── components/
│   ├── layouts/
│   ├── navigation/
│   └── ui/
└── dashboard.blade.php
```

La interfaz utiliza Blade, Tailwind CSS 4 y JavaScript nativo. Incluye modos
claro y oscuro, respeta la preferencia del sistema y conserva la selección en
el navegador. Está adaptada de los nodos `13:11`, `15:598` y `15:1219` del
archivo aprobado en Figma.

## Verificación

```powershell
C:\wamp64\bin\php\php8.2.29\php.exe artisan test
npm run build
C:\wamp64\bin\php\php8.2.29\php.exe vendor\bin\pint --test
```

Consulta `AGENT.md` y los documentos funcionales de la raíz antes de modificar
el alcance o la arquitectura.
