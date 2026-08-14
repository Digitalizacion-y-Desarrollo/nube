# Matriz final de seguridad y auditoría

Revisión cerrada el 28 de julio de 2026 para el MVP de Nube Municipal.

## Matriz de rutas y autorización

Todas las rutas autenticadas usan `access.session`. Las vistas de sección agregan
`access.permission`; las operaciones sobre recursos se autorizan en Policies
después de validar su `FormRequest`.

| Ruta | Operación | Permiso o middleware | Policy / alcance | Auditoría |
|---|---|---|---|---|
| `GET /login`, `GET /forgot-password` | Formularios públicos | `guest` | No aplica | No |
| `POST /login` | Iniciar sesión | `guest`, CSRF | API de Accesos + `nube_inicio_ver` | `auth.login` |
| `POST /forgot-password` | Solicitar recuperación | `guest`, CSRF | Respuesta neutra ante enumeración | No |
| `GET /` | Dashboard | `access.session` | Consultas limitadas al usuario y Policies | No |
| `GET /buscar` | Búsqueda global | `access.session` | `FilePolicy::view` y `FolderPolicy::view` | No |
| `GET /mis-archivos[/{folder}]` | Explorar privados | `nube_mis_archivos_ver` | Propietario | No |
| `GET /mi-departamento[/{folder}]` | Explorar colaborativos | `nube_departamento_ver` | Mismo departamento y alcance colaborativo | No |
| `GET /publicos[/{folder}]` | Explorar públicos | `nube_publicos_ver` | Contenido público interno | No |
| `GET /papelera` | Ver papelera | `nube_papelera_ver` | Recursos eliminados administrables por el actor | No |
| `GET /admin` | Resumen administrativo | `access.session`, `superuser` | Rol exacto `superuser`; consulta global | No |
| `GET /admin/{sección}` | Archivos, departamentos, usuarios, papelera, auditoría y configuración | `access.session`, `superuser` | Rol exacto `superuser`; sólo lectura | No |
| `GET /admin/usuarios[/{user}]` | Supervisión de usuarios y su detalle | `access.session`, `superuser` | Rol exacto `superuser`; sólo lectura, sin rutas POST, PATCH ni DELETE | No |
| `GET /admin/papelera` | Papelera global | `access.session`, `superuser` | Rol exacto `superuser`; consulta | No |
| `GET /admin/archivos/{file}/descargar` | Descarga administrativa | `access.session`, `superuser`, `admin.permission` | `FilePolicy::downloadAdministrative` (sólo activos) | `admin.file.downloaded` |
| `PATCH /admin/archivos/{file}/visibilidad` | Reclasificación administrativa | `access.session`, `superuser`, `admin.permission` | `FilePolicy::changeVisibilityAdministrative` | `admin.file.visibility_changed` / `admin.file.sharing_configured` |
| `DELETE /admin/archivos/{file}` | Enviar a papelera | `access.session`, `superuser`, `admin.permission` | `FilePolicy::deleteAdministrative` + confirmación en modal | `admin.file.trashed` |
| `POST /admin/papelera/archivos/{file}/restaurar` | Restaurar archivo | `access.session`, `superuser`, `admin.permission` | `FilePolicy::restoreAdministrative` (sólo elementos en papelera) | `admin.trash.file_restored` |
| `DELETE /admin/papelera/archivos/{file}` | Eliminar archivo definitivamente | `access.session`, `superuser`, `admin.permission` | `FilePolicy::forceDeleteAdministrative` + confirmación por nombre exacto | `admin.trash.file_purged` |
| `POST /admin/papelera/carpetas/{folder}/restaurar` | Restaurar carpeta | `access.session`, `superuser`, `admin.permission` | `FolderPolicy::restoreAdministrative` (sólo elementos en papelera) | `admin.trash.folder_restored` |
| `DELETE /admin/papelera/carpetas/{folder}` | Eliminar carpeta definitivamente | `access.session`, `superuser`, `admin.permission` | `FolderPolicy::forceDeleteAdministrative` + confirmación por nombre exacto + carpeta sin contenido retenido | `admin.trash.folder_purged` |
| `GET /admin/auditoria[/{log}]` | Bitácora y detalle del evento | `access.session`, `superuser` | Rol exacto `superuser`; sólo lectura, sin rutas POST, PATCH ni DELETE | No |
| `GET /admin/configuracion` | Configuración operativa y estado | `access.session`, `superuser` | Rol exacto `superuser`; sólo lectura, sin rutas de escritura de configuración | No |
| `POST /admin/configuracion/verificar-accesos` | Comprobar conexión con Accesos | `access.session`, `superuser`, CSRF | Rol exacto `superuser`; no modifica configuración | `admin.settings.connection_checked` |
| `POST /mis-archivos/carpetas` | Crear carpeta | permiso `*_crear_carpeta` | `FolderPolicy::create` | `folder.created` |
| `PATCH /mis-archivos/carpetas/{folder}` | Renombrar carpeta | permiso `*_renombrar` | `FolderPolicy::update` | `folder.renamed` |
| `PATCH /mis-archivos/carpetas/{folder}/mover` | Mover carpeta | permiso `*_mover` | `FolderPolicy::move` + destino compatible | `folder.moved` |
| `DELETE /mis-archivos/carpetas/{folder}` | Eliminar carpeta | permiso `*_eliminar` | `FolderPolicy::delete` | `folder.deleted` |
| `PATCH /carpetas/{folder}/visibilidad` | Reclasificar carpeta | permiso `*_publicar` | `FolderPolicy::changeVisibility` | `folder.visibility_changed` |
| `POST /mis-archivos/archivos` | Cargar archivo | permiso `*_subir` | `FilePolicy::upload` + destino válido | `file.uploaded` |
| `GET /mis-archivos/archivos/{file}/descargar` | Descargar | permiso `*_descargar` | `FilePolicy::download` | `file.downloaded` |
| `PATCH /mis-archivos/archivos/{file}` | Renombrar | permiso `*_renombrar` | `FilePolicy::update` | `file.renamed` |
| `PATCH /mis-archivos/archivos/{file}/mover` | Mover | permiso `*_mover` | `FilePolicy::move` + destino del mismo departamento | `file.moved` |
| `DELETE /mis-archivos/archivos/{file}` | Enviar a papelera | permiso `*_eliminar` | `FilePolicy::delete` | `file.deleted` |
| `PATCH /archivos/{file}/visibilidad` | Reclasificar archivo | permiso `*_publicar` | `FilePolicy::changeVisibility` | `file.visibility_changed` |
| `POST /papelera/archivos/{file}/restaurar` | Restaurar | `nube_papelera_restaurar` | `FilePolicy::restore` + destino válido | `file.restored` |
| `DELETE /papelera/archivos/{file}/permanente` | Eliminar definitivamente | permiso `*_eliminar` | `FilePolicy::forceDelete` | `file.permanently_deleted` |
| `GET /perfil` | Ver perfil propio | `access.session` | Actor autenticado; sólo sus datos | No |
| `GET /perfil/foto` | Servir la foto propia | `access.session` | Devuelve únicamente la foto del actor; `404` si no tiene | No |
| `POST /perfil/foto` | Cambiar foto de perfil | `access.session`, CSRF | Validación de imagen, extensión, MIME y tamaño | `profile.avatar_updated` |
| `DELETE /perfil/foto` | Volver a la foto predeterminada | `access.session`, CSRF | Actor autenticado | `profile.avatar_removed` |
| `POST /logout` | Cerrar sesión | `access.session`, CSRF | Actor autenticado | `auth.logout` |

`*` se resuelve por clasificación: `nube_mis_archivos`, `nube_departamento` o
`nube_publicos`. `nube_administracion_administrar` concede la capacidad
funcional global, pero no convierte contenido privado ajeno en visible.

## Aislamiento por rol y recurso

- `superuser` habilita únicamente las rutas administrativas de consulta.
  No sustituye permisos efectivos ni Policies para operar sobre archivos o
  carpetas. Tener el permiso heredado `nube_administracion_administrar` sin el
  rol no permite entrar a `/admin`.
- El dashboard administrativo agrega únicamente metadatos de conteo y tamaño.
  Los rankings no consultan ni muestran rutas físicas, nombres almacenados,
  checksums, tokens o claves.
- La supervisión de usuarios es estrictamente de consulta: presenta identidad,
  departamento, estado, roles, permisos efectivos, sesión, consumo, archivos y
  actividad auditada, sin exponer `path`, `stored_name` ni `checksum`, y sin
  rutas locales para crear o modificar usuarios, roles o permisos.
- La papelera global separa consulta de operación: el rol `superuser` permite
  ver los elementos eliminados, pero restaurar o purgar exige además
  `nube_administracion_administrar` y la Policy administrativa correspondiente.
- La eliminación definitiva desde la papelera global exige que el superusuario
  escriba el nombre exacto del recurso. La comprobación se valida en el servidor
  y no depende del diálogo del navegador.
- Las carpetas sólo pueden purgarse cuando no retienen archivos ni subcarpetas,
  porque `files.folder_id` y `folders.parent_id` son `restrictOnDelete`.
- `files.deleted_by` y `folders.deleted_by` conservan al actor del borrado
  lógico. No son campos asignables en masa: se escriben desde el modelo tras el
  borrado y se limpian al restaurar. La purga automática del sistema no fija
  actor.
- La bitácora administrativa es estrictamente de consulta. `AuditLog` no expone
  rutas de escritura, no tiene `updated_at` y la interfaz sólo ofrece listado y
  detalle.
- El campo `details` se muestra con una redacción defensiva: `path`,
  `stored_name`, `checksum` y `disk`, además de cualquier clave que contenga
  contraseña, token, autorización, cookie, secreto o clave de sistema, se
  sustituyen recursivamente por `[OCULTO]`.
- Las acciones administrativas se distinguen por el prefijo `admin.` de su
  clave; el filtro de origen usa esa misma convención, de modo que un evento
  nuevo queda clasificado sin cambios de esquema.
- El filtro por departamento de la bitácora resuelve el área del actor, porque
  `audit_logs` no almacena departamento propio.
- El panel de configuración no expone tokens, la clave del sistema, contraseñas
  ni las rutas físicas de los archivos. Sí muestra la raíz del disco relativa al
  proyecto, porque es configuración del despliegue y no la ubicación de un
  recurso concreto; nunca se imprime la ruta absoluta del servidor.
- El estado del API de Accesos se apoya en dos fuentes distintas y explícitas:
  la última revalidación de la sesión, que es evidencia de que el API respondió,
  y una comprobación en vivo bajo demanda. El panel no lanza peticiones externas
  al cargarse, y el error de la comprobación se resume sin devolver el cuerpo de
  la respuesta remota.
- La única ruta de escritura de la sección no cambia configuración: sólo ejecuta
  la comprobación y registra `admin.settings.connection_checked`.
- Las rutas administrativas de escritura tienen tres capas independientes:
  `superuser` comprueba el rol, `admin.permission` exige el permiso funcional
  `nube_administracion_administrar` explícito en la sesión, y la Policy
  administrativa valida además el estado del recurso. Ninguna capa sustituye a
  las otras.
- `EnsureAdministrativePermission` no admite comodín: a diferencia de
  `EnsureAccessPermission`, el permiso debe aparecer literalmente. Si Accesos
  deja de devolverlo, la siguiente revalidación de sesión cierra la escritura
  aunque la copia local todavía lo conserve.
- La clave del permiso administrativo es `nube_administracion_administrar`,
  conforme a la convención de guion bajo y prefijo por recurso. No usar la
  variante con puntos.
- Los identificadores inválidos, los intentos de path traversal y los recursos
  en un estado incompatible con la operación se resuelven como `404` antes de
  llegar a la Policy, de modo que la ruta falla cerrada.
- Los errores de las operaciones administrativas devuelven mensajes neutros: no
  incluyen rutas físicas, nombres almacenados ni el cuerpo de la excepción.
- La foto predeterminada son las iniciales del usuario, generadas como SVG
  embebido en un `data:` URI. No carga recursos externos, no incluye scripts y
  no requiere relajar la política de seguridad de contenido, que ya admite
  `data:` en `img-src`.
- La foto de perfil es un dato **local**: el sistema de Accesos no la gestiona y
  la sincronización de login no la toca. Se guarda en el disco privado bajo
  `perfiles/{user_id}/` con nombre físico aleatorio, nunca con el nombre
  original del archivo.
- `users.avatar_path` no es asignable en masa y la ruta física no se muestra en
  ninguna vista. La imagen se sirve por controlador y sólo al propio usuario:
  `GET /perfil/foto` devuelve la foto de quien la solicita, nunca la de otro.
- La carga valida imagen, extensión, MIME y tamaño máximo configurable
  (`NUBE_AVATAR_MAX_SIZE_KB`, 10 MB por omisión). Al reemplazar la foto se borra
  la anterior; si falla el registro, se elimina la imagen recién subida.
- La vista previa se construye con `URL.createObjectURL`, que produce una URL
  `blob:` ya admitida por `img-src`. La comprobación de tamaño y formato en el
  navegador es sólo una cortesía: la validación del servidor es la que decide.
- `admin_area` no es un permiso universal. Sólo puede administrar recursos
  colaborativos de su mismo departamento y necesita además el permiso funcional
  correspondiente.
- El contenido privado sólo es accesible y administrable por su propietario.
- El contenido colaborativo exige coincidencia de departamento. En alcance
  `selected`, los usuarios comunes deben ser colaboradores explícitos y cada
  acción requiere además su bandera interna `can_view`, `can_download`,
  `can_rename`, `can_move` o `can_delete`.
- Las banderas por recurso son datos internos de Nube Municipal; no se agregan
  al catálogo ni al payload del API de accesos. La autorización efectiva es la
  intersección entre el permiso funcional del API y la bandera interna.
- El contenido público puede verse con su permiso; sólo su propietario o un
  usuario con `nube_administracion_administrar` puede administrarlo.
- Las carpetas destino deben existir, no estar eliminadas y pertenecer al mismo
  departamento que el recurso.

## Eventos y actor

Los eventos de archivo se registran en `FileObserver`, salvo la descarga, que se
registra en el controlador. Las carpetas y la autenticación se registran en sus
controladores. Cada evento HTTP conserva `user_id`, IP, agente de usuario, tipo e
identificador de recurso cuando aplica. La purga programada registra actor nulo,
porque es una acción del sistema. Los cambios en `stored_name`, disco, ruta y
checksum sólo se señalan como modificados: su valor no se copia al log.

## Hallazgos corregidos

- Se añadió CSP con nonce para scripts, protección contra framing, MIME sniffing,
  fuga de referencia y capacidades innecesarias del navegador.
- Se activó el cifrado de sesión por defecto y se documentaron cookies seguras,
  `HttpOnly` y `SameSite`.
- Se limitaron hosts al host de `APP_URL` y los proxies sólo se confían cuando
  aparecen explícitamente en `TRUSTED_PROXIES`.
- Se eliminó la configuración de `storage:link`; el disco `nube` vive fuera de
  `public` y cuenta con denegación adicional para Apache.
- Se añadió redacción recursiva de contraseñas, tokens, encabezados de
  autorización, cookies, claves de sistema y claves API antes de escribir logs.
- Se verificaron validación por `FormRequest`, UUID, nombres, MIME, extensión,
  tamaño, asignación de campos y respuestas de error sin datos secretos.

## Pruebas ofensivas cubiertas

- Acceso horizontal entre propietarios y departamentos.
- Límites de `admin_area`, colaboración seleccionada y cambios de departamento.
- UUID inexistente o mal formado y secuencias de path traversal.
- Campos adicionales para intentar sustituir propietario, departamento, disco,
  ruta o visibilidad.
- CSRF mediante el grupo web de Laravel en todas las mutaciones.
- Descarga exclusivamente por controlador y Policy.
- Ausencia de enlace público y respuesta 404 ante `/storage/...`.
- Actor correcto en carga, descarga, cambio, borrado, restauración y cierre de
  sesión; actor nulo sólo en purga de sistema.

## Configuración obligatoria de producción

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://nube.ejemplo.gob.mx
LOG_LEVEL=warning
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
TRUSTED_PROXIES=10.0.0.10,10.0.0.11
```

El document root debe apuntar exclusivamente a `public/`; HTTPS debe terminar en
el servidor o en uno de los proxies enumerados. No ejecutar `storage:link`. Las
claves reales deben residir en el administrador de secretos o `.env` del entorno,
nunca en el repositorio. Después de configurar producción se debe ejecutar
`php artisan config:cache`.

## Riesgos aceptados y controles operativos

- `style-src` conserva `'unsafe-inline'` por los estilos inline que requieren la
  interfaz actual. Los scripts no permiten `'unsafe-inline'` y usan nonce por
  respuesta. Retirar esta excepción requiere migrar todos los estilos dinámicos.
- HSTS sólo se emite en producción sobre solicitudes HTTPS para no bloquear
  entornos locales. Su eficacia depende de terminar HTTPS y reenviar el protocolo
  desde un proxy declarado.
- `SESSION_SAME_SITE=lax` mantiene compatibilidad con los flujos actuales. Puede
  elevarse a `strict` después de validar integraciones y enlaces de entrada.
- La disponibilidad y revocación del API de Accesos siguen siendo controles
  externos; la sesión local se revalida según el intervalo configurado.
