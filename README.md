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
NUBE_MAX_FILE_SIZE_KB=204800
NUBE_TRASH_RETENTION_DAYS=30
```

Para permitir archivos de hasta 200 MB, el servidor también debe usar:

```ini
upload_max_filesize = 200M
post_max_size = 210M
```

En Nginx configura `client_max_body_size 210M`. Apache no requiere un límite
adicional cuando `LimitRequestBody` permanece en `0`; si se define, debe ser de
al menos 209715200 bytes. Después de modificar PHP o el servidor web, reinicia
el servicio correspondiente.

## Papelera y auditoría

Los archivos permanecen 30 días en Papelera de forma predeterminada. El plazo
puede cambiarse con `NUBE_TRASH_RETENTION_DAYS`. Cada archivo puede eliminarse
manualmente de forma permanente después de una confirmación SweetAlert2.

La purga automática está registrada en el scheduler de Laravel a las 02:00:

```bash
php artisan files:purge-trash
```

En producción debe ejecutarse el scheduler de Laravel cada minuto:

```cron
* * * * * cd /ruta/a/nube && php artisan schedule:run >> /dev/null 2>&1
```

Las creaciones, modificaciones, eliminaciones, restauraciones y purgas de
archivos se registran internamente en `audit_logs`. Las ejecuciones del sistema
quedan con `user_id` nulo y las acciones de usuarios conservan su actor.

## Clasificación y acceso

Las carpetas y los archivos pueden crearse como privados, colaborativos o
públicos internos. Los recursos colaborativos pueden compartirse con todo el
departamento o con una selección de personas activas del mismo departamento.
La selección consulta `GET /api/integrations/users` con el token de la sesión y
filtra la respuesta por el departamento del usuario autenticado. Si el servicio
no está disponible, el formulario muestra el error y permite reintentar sin
usar silenciosamente información local desactualizada.
Los archivos y las carpetas pueden reclasificarse sin alterar la visibilidad
independiente de su contenido. Estos cambios generan los eventos
`file.visibility_changed` y `folder.visibility_changed`, respectivamente.

- Privado: acceso exclusivo del propietario.
- Colaborativo: lectura y descarga dentro del mismo departamento; modificación
  exclusiva del propietario. Puede limitarse a colaboradores seleccionados.
- Público interno: lectura y descarga con los permisos públicos; modificación
  por el propietario o un administrador.

Las operaciones utilizan los permisos del recurso correspondiente, por ejemplo
`nube_departamento_descargar`, `nube_publicos_descargar` y los permisos
`*_publicar` para cambiar la clasificación.

La clasificación del contenedor no se hereda: una carpeta pública puede
contener archivos públicos, colaborativos o privados. Cada elemento conserva
su propia Policy y nunca se vuelve visible únicamente por estar dentro de una
carpeta pública.

Si el creador cambia de departamento, conserva sus archivos y carpetas
privados, pero pierde acceso al contenido colaborativo del área anterior. El
recurso mantiene su `department_id` y su ruta física originales, y el área
nueva no obtiene acceso automático. Un usuario del área propietaria con rol
exacto `admin_area` puede administrar ese contenido cuando también cuenta con
el permiso funcional requerido para la acción.

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
