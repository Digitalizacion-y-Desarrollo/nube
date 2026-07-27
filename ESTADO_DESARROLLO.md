# Estado de desarrollo — Nube Empresarial

Este archivo es la bitácora de continuidad del proyecto. Debe actualizarse al
final de cada sesión de trabajo y consultarse antes de iniciar una nueva.

## Última actualización

- Fecha: 27 de julio de 2026.
- Estado general: Épicos 01 a 06 en Revisión y QA; Épicos 07 a 10
  reorganizados en Backlog con únicamente sus tareas faltantes.
- Próximo trabajo: Épico 07 — Cierre visual y experiencia de usuario.
- Rama o commit: los cambios actuales permanecen en el árbol de trabajo local;
  no se creó ningún commit durante esta sesión.

## Tablero de Trello

- Épico 01 — Preparación técnica y estructura: **Revisión y QA**.
- [Épico 02 — Base de datos y modelos Eloquent](https://trello.com/c/66AfrJsG/2-epic-02-base-de-datos-y-modelos-eloquent):
  **Revisión y QA**.
- [Épico 03 — Integración con sistema de accesos](https://trello.com/c/lxIo64F4/3-epic-03-integraci%C3%B3n-con-sistema-de-accesos):
  **Revisión y QA**.
- [Épico 04 — Explorador y carpetas privadas](https://trello.com/c/qSz1mLPp/4-epic-04-explorador-y-carpetas-privadas):
  **Revisión y QA**.
- [Épico 05 — Gestión de archivos privados](https://trello.com/c/XpKGqXnz/5-epic-05-gesti%C3%B3n-de-archivos-privados):
  **Revisión y QA**.
- [Épico 06 — Revisión de archivos colaborativos y públicos](https://trello.com/c/k28nqTpV):
  **Revisión y QA**.
- [Épico 07 — Cierre visual y experiencia de usuario](https://trello.com/c/8ZQ0TUGu):
  **Backlog; siguiente trabajo**.
- [Épico 08 — Revisión final de seguridad y auditoría](https://trello.com/c/4AxmA7hy):
  **Backlog**.
- [Épico 09 — QA final y cobertura de regresión](https://trello.com/c/Azlh38l5):
  **Backlog**.
- [Épico 10 — Preparación de despliegue y entrega del MVP](https://trello.com/c/QTyxV3SE):
  **Backlog**.

## Trabajo completado

### Épico 01 — Preparación técnica

- Proyecto Laravel 12 con Blade, Tailwind CSS 4, Vite y JavaScript nativo.
- Layout principal, navegación y componentes Blade reutilizables.
- Identidad visual base y soporte para temas claro y oscuro.
- Activos aprobados de Figma almacenados localmente.

### Épico 02 — Base de datos y modelos

- Migraciones para:
  - `departments`
  - `users`
  - `roles`
  - `user_roles`
  - `permissions`
  - `user_permissions`
  - `folders`
  - `files`
  - `audit_logs`
- Modelos, relaciones, factories y seeders demostrativos.
- UUID para carpetas y archivos.
- Eliminación lógica para carpetas y archivos.
- Llaves foráneas, índices y reglas de eliminación verificadas.
- Catálogo fijo de permisos eliminado de los seeders de producción.

### Épico 03 — Sistema de accesos

- `AccessApiService` para:
  - Login.
  - Consulta de sesión mediante `/api/auth/me`.
  - Logout.
  - Recuperación de contraseña.
  - Consulta de usuarios, departamentos y cambio de contraseña.
- Token Bearer almacenado únicamente en la sesión del servidor.
- Sincronización transaccional de:
  - Departamento padre e hijo.
  - Usuario.
  - Roles informativos.
  - Permisos efectivos.
- Los permisos locales del usuario se reemplazan exactamente con la lista
  recibida del API.
- Los permisos globales no se eliminan durante el login.
- Middleware de sesión con revalidación periódica del token.
- Verificación obligatoria del permiso `nube_inicio_ver`.
- Logout central y local.
- Auditoría de `auth.login` y `auth.logout`.
- Manejo de credenciales inválidas, cuenta inactiva, falta de acceso,
  validación, token vencido, errores del servidor y API no disponible.
- Login responsive basado en la sección `32:2` y el frame `13:11` de Figma.
- Estados visuales implementados:
  - Normal.
  - Campos incompletos.
  - Credenciales incorrectas.
  - Falta de permiso.
  - Cuenta inactiva.
  - Error de conexión.
  - Autenticando.

### Épico 04 — Explorador y carpetas privadas

- Secciones iniciales implementadas con rutas independientes:
  - Mis archivos.
  - Mi departamento.
  - Públicos.
  - Papelera.
- Consultas limitadas por propietario, departamento, visibilidad y eliminación
  lógica según la sección.
- Middleware de permisos para las claves `nube_*_ver`, con capacidad
  administrativa como excepción explícita.
- Navegación de escritorio, lateral móvil e inferior conectada a las rutas
  reales y filtrada por permisos.
- Listado responsive de carpetas y archivos, contadores y estados vacíos.
- Pruebas Feature para aislamiento de contenido privado, departamental, público
  y eliminado.
- Navegación jerárquica por subcarpetas privadas, departamentales y públicas.
- Breadcrumbs completos, ruta lógica visible y regreso a la carpeta anterior.
- Creación de carpetas privadas raíz y subcarpetas.
- Asociación automática de propietario, departamento y visibilidad privada.
- Validación de nombre obligatorio, longitud máxima, caracteres inválidos y
  duplicados sin distinción entre mayúsculas y minúsculas en el mismo nivel.
- Bloqueo de creación dentro de carpetas ajenas, eliminadas, colaborativas o
  públicas.
- Renombrado exclusivo del propietario con actualización transaccional de
  `path_cache` para todos los descendientes.
- Eliminación lógica exclusiva de carpetas vacías; se bloquean carpetas con
  subcarpetas o archivos activos.
- `FolderPolicy`, Form Requests separados y `FolderPathService`.
- Auditoría de `folder.created`, `folder.renamed` y `folder.deleted`, sin
  registrar rutas físicas.
- Modales responsive para crear, renombrar y eliminar, mensajes de éxito,
  errores de validación y estados vacíos.
- Pruebas de manipulación de UUID, aislamiento por propietario y departamento,
  herencia de visibilidad, rutas lógicas y restricciones de eliminación.

### Épico 05 — Gestión de archivos privados

- Modal de carga con selector de carpeta destino y estado visual de carga.
- Límite máximo ampliado a **200 MB** y alineado en:
  - Laravel: `NUBE_MAX_FILE_SIZE_KB=204800`.
  - PHP CLI: `upload_max_filesize=200M`, `post_max_size=210M`.
  - Apache local: valores aplicados mediante `.htaccess` y verificados por HTTP.
- Validación independiente de extensión, MIME y tamaño para PDF, documentos de
  Office, TXT, CSV, JPG, JPEG, PNG y ZIP.
- UUID y nombre físico seguro; nombre original y visible conservados por
  separado.
- Checksum SHA-256 y registro completo de metadatos.
- Guardado privado mediante Laravel Storage fuera de `public`.
- Compensación transaccional:
  - Si falla la base de datos, se elimina el archivo físico nuevo.
  - Si falla el almacenamiento, no se crea el registro.
  - Si falla un movimiento, eliminación o restauración, se intenta devolver el
    archivo a su ubicación anterior.
- Descarga exclusiva mediante controlador, `FilePolicy` y comprobación de
  existencia física.
- Renombrado de `display_name` sin modificar el nombre físico.
- Movimiento entre carpetas privadas propias y actualización de la ruta física.
- Eliminación lógica con traslado físico a `papelera/`.
- Retención configurable de **30 días** en Papelera
  (`NUBE_TRASH_RETENTION_DAYS=30`), con fecha de vencimiento visible.
- Eliminación permanente manual protegida por Policy y confirmación mediante
  SweetAlert2.
- Purga automática diaria a las 02:00 mediante `files:purge-trash`; elimina el
  archivo físico y su registro definitivo.
- Restauración a la raíz o a cualquier carpeta privada activa del propietario.
- `FileController`, `FileStorageService`, `FilePolicy` y Form Requests para
  carga, renombrado, movimiento y restauración.
- Auditoría interna mediante `FileObserver` para toda creación, modificación,
  eliminación lógica, restauración y eliminación permanente, incluso fuera de
  los controladores; las descargas también quedan registradas.
- Acciones responsive de descarga, renombrado, movimiento, eliminación y
  restauración filtradas por permisos.
- Pruebas de límite exacto de 200 MB, MIME/extensión, UUID ajenos, fallos de
  almacenamiento y base de datos, archivo físico faltante y ciclo completo de
  papelera, vencimiento, purga y auditoría automática.

### Épico 06 — Archivos colaborativos y públicos

- Carga directa con clasificación privada, colaborativa o pública y selector
  dinámico en el modal.
- Creación de carpetas privadas, colaborativas y públicas en cualquier nivel
  administrable.
- Reclasificación de carpetas entre privada, colaborativa y pública, incluida
  la selección de colaboradores del mismo departamento, sin modificar la
  clasificación independiente de sus archivos o subcarpetas.
- Visibilidad independiente entre carpetas y contenido: un contenedor público
  puede incluir archivos públicos, colaborativos o privados.
- Ante un cambio de departamento, el creador conserva sus recursos privados
  pero pierde acceso al contenido colaborativo del área anterior. Los usuarios
  del área propietaria con rol `admin_area` y el permiso funcional
  correspondiente pueden ver, editar y eliminar ese contenido.
- Colaboración para todo el departamento o para una selección de personas
  activas del mismo departamento.
- Selección de colaboradores alimentada por
  `GET /api/integrations/users`, usando el Bearer de la sesión y
  `ACCESS_SYSTEM_KEY`; la respuesta se filtra por departamento y sincroniza
  las identidades locales necesarias para las relaciones.
- Tablas `folder_collaborators` y `file_collaborators`, con validación de
  departamento, estado activo y selección mínima.
- Rutas físicas separadas en `privados/`, `colaborativos/` y `publicos/`.
- Cambio de visibilidad con traslado físico transaccional a la raíz de la nueva
  sección y eliminación de la referencia a carpetas incompatibles.
- Consultas colaborativas limitadas al departamento y consultas públicas
  internas disponibles mediante los permisos efectivos.
- `FilePolicy` completa por visibilidad para ver, descargar, renombrar, mover,
  eliminar, restaurar y reclasificar.
- Archivos colaborativos modificables únicamente por el propietario; archivos
  públicos modificables por propietario o administrador.
- Permisos de publicación aplicados según el recurso de origen:
  `nube_mis_archivos_publicar`, `nube_departamento_publicar` y
  `nube_publicos_publicar`.
- Descarga segura para compañeros del mismo departamento y usuarios autorizados
  de otros departamentos en contenido público.
- Restauración de archivos colaborativos y públicos a su ubicación física
  correspondiente.
- Etiquetas de clasificación y acciones responsive en las vistas.
- Auditoría automática `file.visibility_changed` y
  `folder.visibility_changed`, sin exponer rutas físicas.
- Seeder demostrativo actualizado con los 27 permisos no administrativos.
- Catorce pruebas Feature nuevas para aislamiento, permisos, almacenamiento,
  auditoría, restauración, contenido mixto e interfaz.

## Decisiones que deben conservarse

- El sistema de accesos es la fuente oficial de usuarios, departamentos, roles
  y permisos.
- Los roles son solamente informativos.
- La autorización funcional se realiza exclusivamente con permisos efectivos.
- Las claves de permisos son globalmente únicas, usan guion bajo y están
  prefijadas por recurso.
- El permiso de entrada es `nube_inicio_ver`; no volver a usar
  `nube.acceder`.
- No duplicar los 28 permisos en un seeder de producción.
- No almacenar contraseñas ni escribir la clave del sistema en código,
  documentación o archivos versionados.
- Combinar permisos con Policies para validar propietario, departamento,
  visibilidad y estado del recurso.
- Los diseños aprobados de Figma siguen siendo la referencia visual.

## Configuración necesaria

Variables documentadas en `.env.example`:

```dotenv
ACCESS_API_URL=https://accesos.digitalneza.com
ACCESS_SYSTEM_KEY=
ACCESS_TIMEOUT=10
ACCESS_SESSION_CHECK_INTERVAL=300
NUBE_MAX_FILE_SIZE_KB=204800
NUBE_TRASH_RETENTION_DAYS=30
```

La clave real debe permanecer únicamente en el `.env` local o en el
administrador de secretos del entorno.

## Verificación al cierre

- Pruebas: **101 aprobadas, 502 aserciones**.
- Laravel Pint: aprobado.
- Compilación de Vite: aprobada.
- Programación diaria: aprobada mediante `php artisan schedule:list`.
- Rutas: aprobadas.
- Compilación de vistas Blade: aprobada.
- `git diff --check`: aprobado.
- `.env.example`: verificado sin clave secreta.
- Limitación: no había un navegador conectado para efectuar la inspección
  visual interactiva final del flujo de archivos. El renderizado Blade, las
  rutas, los flujos HTTP, Apache, el almacenamiento simulado y la compilación
  responsive sí fueron verificados.

Comandos de comprobación:

```powershell
php artisan test
vendor\bin\pint --test
npm run build
php artisan route:list --except-vendor
```

## Punto exacto para retomar

El **Épico 06 — Revisión de archivos colaborativos y públicos** está en
Revisión y QA. El siguiente trabajo de implementación es el
**Épico 07 — Cierre visual y experiencia de usuario**, comenzando por sustituir
los datos estáticos del dashboard y habilitar búsqueda, filtros, ordenamiento y
paginación.

Antes de comenzar, revisar también `AGENT.md`,
`Plan_de_Desarrollo_por_Fases_Nube_Municipal.md`,
`Base_de_Datos_Nube_Municipal.md` y `Propuesta_MVP_Nube_Municipal.md`.
