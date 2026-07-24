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
GET  /                 Dashboard protegido
GET  /login            Inicio de sesión
POST /login            Autenticación central y sincronización
GET  /forgot-password  Formulario de recuperación
POST /forgot-password  Solicitud de recuperación al API
POST /logout           Cierre de sesión central y local
GET  /up               Estado de Laravel
```

El dashboard exige una sesión local respaldada por un token Bearer del sistema
central y el permiso efectivo `nube_inicio_ver`.

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
el navegador. El login responsive y sus estados están adaptados de la sección
`32:2` y del frame `13:11`; el resto de la interfaz parte de los nodos
`15:598` y `15:1219` del archivo aprobado en Figma.

## Modelo de datos

El modelo local utiliza exclusivamente las tablas de dominio aprobadas:

```text
departments
users
roles
user_roles
permissions
user_permissions
folders
files
audit_logs
```

MySQL se configura explícitamente con InnoDB para garantizar llaves foráneas,
índices sobre `utf8mb4` y las reglas de eliminación. Las carpetas y archivos
utilizan UUID y eliminación lógica.

Para reconstruir la base local y cargar los permisos y datos demostrativos:

```powershell
C:\wamp64\bin\php\php8.2.29\php.exe artisan migrate:fresh --seed
```

En producción, los permisos se sincronizan desde el sistema de accesos durante
el inicio de sesión; no existe un catálogo local fijo. `DatabaseSeeder` solo
carga permisos y registros demostrativos en los entornos `local` y `testing`.

## Sistema de accesos

La conexión con el sistema central se configura exclusivamente mediante
variables de entorno:

```dotenv
ACCESS_API_URL=https://accesos.digitalneza.com
ACCESS_SYSTEM_KEY=
ACCESS_TIMEOUT=10
ACCESS_SESSION_CHECK_INTERVAL=300
```

`ACCESS_SYSTEM_KEY` debe configurarse localmente o mediante el administrador de
secretos del entorno. Nunca debe escribirse en el código, documentación,
registros o archivos versionados.

`AccessApiService` encapsula los endpoints documentados del sistema central:

```text
POST /api/auth/login
GET  /api/auth/me
POST /api/auth/logout
POST /api/auth/forgot-password
GET  /api/integrations/users
PUT  /api/integrations/users/{id}/password
GET  /api/departamentos
```

Las respuestas de autenticación y colecciones utilizan objetos de datos
tipados. Los errores `401`, `403`, `404`, `422`, `429`, `500`, las respuestas
inválidas y los fallos de conexión se traducen a excepciones específicas con
mensajes seguros. Las pruebas del cliente usan `Http::fake()` y no realizan
solicitudes al API real.

Después de cada login válido, la aplicación sincroniza en una sola transacción
el departamento padre e hijo, el usuario, los roles informativos y la lista
exacta de permisos efectivos. El token permanece únicamente en la sesión del
servidor. El middleware revalida periódicamente `/api/auth/me`, elimina la
sesión ante un token vencido y nunca autoriza por nombre de rol.

## Verificación

```powershell
C:\wamp64\bin\php\php8.2.29\php.exe artisan test
npm run build
C:\wamp64\bin\php\php8.2.29\php.exe vendor\bin\pint --test
```

Consulta `AGENT.md` y los documentos funcionales de la raíz antes de modificar
el alcance o la arquitectura.
