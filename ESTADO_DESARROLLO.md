# Estado de desarrollo — Nube Municipal

Este archivo es la bitácora de continuidad del proyecto. Debe actualizarse al
final de cada sesión de trabajo y consultarse antes de iniciar una nueva.

## Última actualización

- Fecha: 13 de agosto de 2026.
- Estado general: Épicos 01 a 08 y 11 a 19 implementados y en Revisión y QA;
  Épico 20 con su parte automatizada y documental completada; Épico 09 en
  Terminado y Épico 10 en Backlog.
- Próximo trabajo: sesión con navegador para el QA manual de los Épicos 06, 07 y
  11 a 20 según `docs/QA_ADMINISTRATIVO.md`, y después el Épico 10 —
  Preparación de despliegue y entrega del MVP.
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
  **Revisión y QA; implementación completada, pendiente evidencia visual
  interactiva**.
- [Épico 08 — Revisión final de seguridad y auditoría](https://trello.com/c/4AxmA7hy):
  **Revisión y QA; implementación y pruebas ofensivas completadas**.
- [Épico 09 — QA final y cobertura de regresión](https://trello.com/c/Azlh38l5):
  **Terminado**.
- [Épico 10 — Preparación de despliegue y entrega del MVP](https://trello.com/c/QTyxV3SE):
  **Backlog**.
- [Épico 11 — Acceso y navegación del superusuario](https://trello.com/c/ici7CwgU):
  **Revisión y QA; implementación y pruebas automatizadas completadas**.
- [Épico 12 — Dashboard administrativo](https://trello.com/c/e2fGenUl):
  **Revisión y QA; implementación y pruebas automatizadas completadas**.
- [Épico 13 — Explorador global de archivos](https://trello.com/c/MO8CqPVh):
  **Revisión y QA; implementación y pruebas automatizadas completadas,
  pendiente revisión visual interactiva**.
- [Épico 14 — Administración de departamentos](https://trello.com/c/Gzk4OMZ9):
  **Revisión y QA; implementación y pruebas automatizadas completadas**.
- [Épico 15 — Supervisión de usuarios](https://trello.com/c/8J62CXfO):
  **Revisión y QA; implementación y pruebas automatizadas completadas**.
- [Épico 16 — Papelera general](https://trello.com/c/Zj9p8IRM):
  **Revisión y QA; implementación y pruebas automatizadas completadas**.
- [Épico 17 — Auditoría administrativa](https://trello.com/c/rPGdppzA):
  **Revisión y QA; implementación y pruebas automatizadas completadas**.
- [Épico 18 — Configuración operativa y estado del sistema](https://trello.com/c/T6WDe9ae):
  **Revisión y QA; implementación y pruebas automatizadas completadas**.
- [Épico 19 — Seguridad y Policies administrativas](https://trello.com/c/cKrAhHgJ):
  **Revisión y QA; implementación y pruebas automatizadas completadas**.
- [Épico 20 — Pruebas, estabilización y cierre administrativo](https://trello.com/c/sEn1yqao):
  **Backlog; parte automatizada y documental completada, pendiente el QA manual
  con navegador**.

## Trabajo completado

### Épico 01 — Preparación técnica

- Proyecto Laravel 12 con Blade, Tailwind CSS 4, Vite y JavaScript nativo.
- Layout principal, navegación y componentes Blade reutilizables.
- Identidad visual base y soporte para temas claro y oscuro.
- Logo institucional unificado mediante `public/assets/img/logo_nube.png` en
  autenticación, navegación personal, administración y favicon.
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
- Permisos internos por colaborador y recurso para ver, descargar, renombrar,
  mover y eliminar. Se almacenan en `folder_collaborators` y
  `file_collaborators`, no forman parte del API de accesos y se combinan con el
  permiso funcional global antes de autorizar cada acción.
- El permiso `Ver` es la base obligatoria de una selección; `Descargar` inicia
  habilitado y las acciones de administración inician deshabilitadas.
- Las carpetas colaborativas transmiten a los archivos nuevos tanto la
  selección de personas como su matriz de permisos, salvo sobrescritura expresa
  durante la carga.
- Movimiento de carpetas habilitado con validación de clasificación,
  departamento, destino, ciclos, duplicados y actualización de rutas
  descendientes.
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
- Permisos privados alineados con los nombres efectivos del API:
  `nube.archivos.subir`, `nube.archivos.descargar`,
  `nube.archivos.eliminar`, `nube.archivos.publicar` y
  `nube_archivos_crear_carpeta`; se conserva compatibilidad temporal con los
  nombres `nube_mis_archivos_*` previamente usados por la aplicación.
- Archivos colaborativos modificables únicamente por el propietario; archivos
  públicos modificables por propietario o administrador.
- Permisos de publicación aplicados según el recurso de origen:
  `nube.archivos.publicar`, `nube_departamento_publicar` y
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

### Épico 07 — Cierre visual y experiencia de usuario

- Dashboard conectado a consultas reales para indicadores, archivos recientes,
  carpetas recientes, fecha actual y actividad auditada del usuario.
- Acciones rápidas del dashboard conectadas a los formularios reales del
  explorador; se retiraron controles decorativos sin comportamiento.
- Búsqueda global en tiempo real por nombre de archivo o carpeta, presentada en
  un overlay central tipo Spotlight con efecto glass.
- Apertura mediante el encabezado o `Ctrl/⌘ + K`, cierre con Escape, debounce,
  cancelación de solicitudes anteriores y navegación con flechas y Enter.
- El endpoint `/buscar` aplica Policies, permisos, departamento, colaboración
  seleccionada y accesibilidad de toda la ruta de carpetas antes de responder.
- Filtros por tipo, clasificación, propietario y rango de fechas.
- Ordenamiento configurable por nombre, fecha o tamaño, en dirección ascendente
  o descendente.
- Paginación de 10, 25 o 50 elementos que conserva filtros, ordenamiento y
  ubicación actual.
- Estados vacíos diferenciados para ubicaciones sin contenido y búsquedas sin
  coincidencias.
- Barra de búsqueda del encabezado conectada al explorador cuando corresponde.
- Selector múltiple de colaboradores con lista desplegable y búsqueda en tiempo
  real por nombre, correo, cargo o rol para crear carpetas y subir archivos.
- Navegación del selector con teclado, filtrado tolerante a acentos, contador de
  personas seleccionadas y estado vacío cuando no existen coincidencias.
- Acciones contextuales dentro de cada carpeta para agregar un archivo o crear
  una subcarpeta; ambos formularios fijan por defecto la ubicación actual.
- Los propietarios pueden seguir agregando contenido privado en carpetas
  privadas creadas antes de un cambio de departamento; los nuevos elementos
  heredan el límite departamental histórico de la carpeta.
- Los formularios de archivo y carpeta detectan la ubicación abierta, muestran
  `Carpeta de destino` y la preseleccionan; la carpeta actual también aparece
  cuando pertenece a un departamento histórico del propietario.
- Los archivos nuevos heredan por defecto la clasificación, el alcance y los
  colaboradores de la carpeta contenedora; el formulario muestra la herencia y
  permite sobrescribirla antes de cargar.
- Al reclasificar archivos o carpetas como colaborativos, el formulario inicia
  en `Personas específicas` y abre automáticamente el selector del departamento.
- Los formularios de creación fijan la clasificación por sección para evitar
  ambigüedad: privado en `Mis archivos`, colaborativo en `Mi departamento` y
  público en `Públicos`; los destinos se limitan a carpetas compatibles.
- Estados globales de foco visible, controles deshabilitados y transiciones
  coherentes en claro y oscuro.
- Modales y navegación móvil con foco inicial, ciclo de tabulación, cierre con
  Escape y devolución del foco al control de origen.
- Contraste reforzado para texto dorado sobre superficies claras sin cambiar el
  color institucional secundario.
- Seis pruebas Feature nuevas para datos reales, filtros, ordenamiento,
  paginación, seguridad de la búsqueda global y renderizado de Spotlight.

### Épico 08 — Revisión final de seguridad y auditoría

- Matriz completa de rutas, middleware, permisos, Policies, roles y eventos de
  auditoría en `docs/SEGURIDAD_Y_AUDITORIA.md`.
- Aislamiento de `admin_area` confirmado: sólo recursos colaborativos del mismo
  departamento y siempre sujeto al permiso funcional correspondiente.
- Cabeceras defensivas globales con CSP, nonce por respuesta, protección de
  framing y MIME sniffing, política de referencia y permisos del navegador.
- Sesiones cifradas por defecto y variables de cookies/proxy documentadas para
  producción.
- Hosts limitados a `APP_URL` y proxies confiables configurables sólo mediante
  una lista explícita.
- Almacenamiento `nube` fuera de `public`, sin `storage:link` configurado y con
  denegación adicional de acceso directo para Apache.
- Redacción recursiva de contraseñas, tokens, autorización, cookies y claves en
  los canales de log.
- Pruebas ofensivas para UUID, path traversal, asignación de campos protegidos,
  acceso horizontal y exposición por `/storage`.
- Verificación HTTP local: cabeceras presentes, POST sin token rechazado con
  419 y acceso directo a almacenamiento rechazado con 404.
- Riesgos aceptados y lista de configuración obligatoria de producción
  documentados.

### Épico 11 — Acceso y navegación del superusuario

- Acceso administrativo reservado al rol exacto `superuser`.
- Grupo de rutas `/admin` protegido por `access.session` y middleware
  `superuser`; los usuarios autenticados sin el rol reciben `403`.
- Sección independiente y responsive con navegación a Resumen, Archivos,
  Departamentos, Usuarios, Papelera, Auditoría y Configuración.
- Panel de consulta conectado a datos reales: indicadores globales, inventario
  de archivos, departamentos, usuarios y roles, papelera y bitácora.
- Configuración operativa presentada sólo en modo lectura, sin exponer claves,
  tokens, contraseñas ni rutas físicas.
- Enlaces bidireccionales para alternar entre administración y la nube
  personal.
- El rol no concede permisos funcionales sobre archivos o carpetas; esas
  acciones continúan protegidas por permisos del API y Policies.
- Cinco pruebas Feature nuevas cubren invitados, `403`, rechazo del permiso
  administrativo heredado, siete secciones, datos reales y ocultamiento de
  rutas físicas.

### Épico 12 — Dashboard administrativo

- Indicadores globales para archivos, carpetas, usuarios y departamentos.
- Conteos separados para recursos activos y elementos en papelera.
- Espacio utilizado separado en archivos activos, papelera y total retenido,
  con unidades legibles desde bytes hasta terabytes.
- Distribución privada, colaborativa y pública con conteos activos y
  eliminados.
- Actividad reciente global conservada en el resumen administrativo.
- Rankings de los cinco departamentos y usuarios con mayor consumo, ordenados
  por almacenamiento total y desglosados entre activo y papelera.
- Estados vacíos explícitos cuando todavía no existe consumo.
- Consultas encapsuladas en `AdminDashboardService`, sin exponer rutas físicas,
  nombres almacenados, tokens, claves o contraseñas.
- Dos pruebas Feature nuevas validan métricas reales, orden de rankings,
  unidades legibles y estados vacíos.

### Épico 13 — Explorador global de archivos

- Inventario global paginado con búsqueda por nombre y filtros por
  departamento, propietario, clasificación, extensión, rango de carga y estado.
- Paginación configurable en 10, 20 o 50 resultados conservando los filtros.
- Vista de metadatos con UUID, nombres, MIME, tamaño, clasificación, propietario,
  departamento, carpeta lógica, colaboración y fechas.
- Ocultamiento explícito de `path`, `stored_name` y `checksum` en listado,
  detalle y eventos de auditoría administrativa.
- Descarga segura a través del controlador y del disco privado `nube`, sin URL
  pública ni acceso directo al almacenamiento.
- Reclasificación consistente del registro y el archivo físico; al cambiar o
  conservar la clasificación colaborativa, el superusuario selecciona todo el
  departamento propietario o personas activas específicas de esa área, con
  permisos internos por persona.
- Envío a papelera mediante confirmación reforzada y el servicio transaccional
  de almacenamiento existente.
- Separación de capacidades: `superuser` consulta; las descargas y mutaciones
  exigen además `nube_administracion_administrar` y Policies administrativas.
- Eventos `admin.file.metadata_viewed`, `admin.file.downloaded`,
  `admin.file.visibility_changed`, `admin.file.sharing_configured` y
  `admin.file.trashed` con actor, IP, agente y metadatos no sensibles.
- Cinco pruebas Feature nuevas cubren filtros combinados, privacidad de
  metadatos, rechazo sin permiso, selección colaborativa por departamento o
  personas y operaciones autorizadas con auditoría.
- El formulario restaura después de cada guardado los colaboradores y permisos
  persistidos; cambiar otra tarjeta o reabrir el modal no vacía la selección ni
  provoca que una sincronización posterior retire colaboradores existentes.

### Épico 14 — Administración de departamentos

- Listado global paginado con búsqueda por nombre o abreviatura y filtro por
  departamentos activos e inactivos.
- Indicadores por área para usuarios activos/totales, archivos, carpetas,
  elementos en papelera y almacenamiento activo/eliminado.
- Estado y fecha exacta de última sincronización, además de referencia relativa.
- Detalle de identidad externa, jerarquía, área superior y dependencias.
- Usuarios relacionados paginados y navegación al listado global filtrado.
- Archivos colaborativos y públicos activos con propietario, carpeta lógica,
  clasificación, tamaño y enlace seguro a metadatos.
- Actividad reciente vinculada por actor, archivo o carpeta del departamento.
- Navegación al inventario de archivos conservando el filtro departamental.
- Ausencia explícita de rutas POST, PATCH o DELETE para departamentos; Accesos
  continúa como única fuente de creación y modificación.
- `AdminDepartmentService` encapsula agregados y relaciones sin mostrar rutas
  físicas, nombres almacenados, checksums, tokens o claves.
- Cuatro pruebas Feature cubren métricas, filtros, contenido relacionado,
  privacidad, navegación y rechazo de mutaciones locales.

### Épico 15 — Supervisión de usuarios

- Listado global paginado de usuarios sincronizados con búsqueda por nombre,
  apellido, correo o identificador externo.
- Filtros combinables por departamento, rol, estado activo/inactivo y tamaño de
  página de 10, 20 o 50 resultados, conservados en la paginación.
- Indicadores globales de usuarios sincronizados, activos e inactivos y
  almacenamiento retenido, activo y en papelera.
- Columnas con departamento, roles informativos, número de permisos efectivos,
  archivos activos y en papelera, almacenamiento activo y eliminado, último
  inicio de sesión y última sincronización relativa.
- Detalle por usuario con identidad, identificador externo, departamento y
  abreviatura, estado, último inicio de sesión y última sincronización con fecha
  exacta y referencia relativa.
- Roles informativos y lista completa de permisos efectivos recibidos del API,
  presentados sólo como consulta.
- Conteo de archivos activos, en papelera y totales, más espacio activo,
  eliminado y retenido en unidades legibles.
- Archivos del usuario paginados, incluidos los enviados a papelera, con
  departamento, carpeta lógica, clasificación, tamaño, estado y enlace a los
  metadatos administrativos.
- Actividad reciente auditada del usuario con acción, IP registrada y fecha.
- Corrección de `AccessUserSynchronizer`: `last_login_at` sólo se actualiza en un
  inicio de sesión real; la revalidación periódica de `EnsureAccessSession`
  actualiza únicamente `last_synced_at`. Antes ambos campos se reescribían en
  cada revalidación, por lo que «último inicio de sesión» reflejaba en realidad
  la última validación de sesión. Una prueba nueva protege esta distinción.
- Navegación al detalle del departamento y al inventario global de archivos
  filtrado por propietario.
- Ausencia explícita de rutas POST, PATCH o DELETE bajo `/admin/usuarios`;
  Accesos continúa siendo la única fuente de identidad, roles y permisos.
- Ocultamiento de `path`, `stored_name` y `checksum` en listado y detalle.
- `AdminUserService` encapsula agregados, detalle y actividad reciente.
- Cinco pruebas Feature cubren métricas y datos de sesión, filtros combinados,
  detalle con permisos, archivos y actividad sin datos sensibles, reserva del
  rol `superuser` y rechazo de mutaciones locales.

### Épico 16 — Papelera general

- Consulta global paginada de archivos y carpetas eliminados, en secciones
  independientes porque cada tipo admite operaciones distintas.
- Filtros compartidos por nombre, persona, departamento y rango de fechas de
  eliminación, con tamaño de página de 10, 20 o 50 conservado en la paginación.
- El filtro por persona abarca tanto al propietario como a quien eliminó.
- Indicadores de archivos y carpetas en papelera, almacenamiento retenido,
  días de retención configurados y archivos a siete días o menos de la purga.
- Fecha exacta de eliminación, referencia relativa y fecha de purga prevista
  para cada archivo.
- Restauración de archivos a su carpeta original cuando sigue activa; si la
  carpeta también fue eliminada, el archivo vuelve a la raíz de su
  clasificación y el archivo físico se mueve de forma transaccional.
- Restauración de carpetas con retorno a la raíz cuando su carpeta superior ya
  no está disponible, actualizando `path_cache` de la carpeta y sus
  descendientes; se bloquea si el nombre ya está ocupado en el destino.
- Eliminación definitiva con confirmación reforzada: además del modal, el
  servidor exige escribir el nombre exacto del recurso.
- La purga de archivos elimina también la copia física del disco privado.
- Las carpetas sólo se purgan cuando no retienen archivos ni subcarpetas,
  porque sus llaves foráneas son `restrictOnDelete`.
- Nuevos campos `deleted_by` en `files` y `folders`, escritos por el rasgo
  `RecordsDeletedBy` tras el borrado lógico y limpiados al restaurar. No son
  asignables en masa y la purga automática del sistema no fija actor.
- La migración rellena `deleted_by` de los elementos ya presentes en la papelera
  a partir de los eventos `file.deleted` y `folder.deleted` de `audit_logs`.
- Separación de capacidades: `superuser` consulta la papelera; restaurar y
  purgar exigen además `nube_administracion_administrar` y las Policies
  `restoreAdministrative` y `forceDeleteAdministrative`.
- Eventos `admin.trash.file_restored`, `admin.trash.file_purged`,
  `admin.trash.folder_restored` y `admin.trash.folder_purged` con actor, IP,
  agente y metadatos no sensibles.
- Ocho pruebas Feature cubren listado con actor y vencimiento, filtros
  combinados, separación entre consulta y operación, restauración a carpeta
  original y a raíz, retorno de carpetas a la raíz, confirmación por nombre
  exacto con borrado físico y bloqueo de purga de carpetas con contenido.

**Limitación conocida:** la purga automática diaria sólo alcanza archivos. Las
carpetas eliminadas permanecen en la papelera hasta que un superusuario las
elimina manualmente; la interfaz lo indica de forma explícita.

### Épico 17 — Auditoría administrativa

- Listado global paginado de `audit_logs` en 25, 50 o 100 resultados,
  conservando los filtros y ordenado por fecha descendente.
- Filtros combinables por texto en acción o identificador de recurso, usuario,
  departamento del actor, acción exacta, tipo de recurso, dirección IP, origen
  administrativo o de usuario y rango de fechas.
- Indicadores de eventos totales, acciones administrativas, acciones de
  usuarios, actores distintos y eventos de las últimas 24 horas.
- El listado resuelve el nombre visible del archivo o carpeta referenciado con
  dos consultas por página, en lugar de una por evento.
- Vista de detalle con actor, correo, departamento, IP, agente de usuario, tipo
  e identificador del recurso, nombre actual y fecha con segundos.
- El campo `details` se presenta formateado con redacción defensiva: `path`,
  `stored_name`, `checksum` y `disk`, más cualquier clave que parezca un
  secreto, se sustituyen recursivamente por `[OCULTO]`.
- Historial de los diez eventos más recientes del mismo recurso, con navegación
  entre ellos.
- Navegación al detalle del usuario y a los metadatos del archivo relacionado.
- Distinción entre acciones administrativas y de usuarios mediante el prefijo
  `admin.` de la clave, expuesta como etiqueta y como filtro de origen; un
  evento nuevo queda clasificado sin cambios de esquema.
- Ausencia explícita de rutas POST, PATCH o DELETE bajo `/admin/auditoria`; el
  modelo `AuditLog` permanece inmutable y sin `updated_at`.
- `AdminAuditService` encapsula consultas, resolución de nombres, resumen y
  redacción.
- Cinco pruebas Feature cubren listado con actor y recurso, filtros combinados,
  separación por origen, detalle sin rutas ni secretos y reserva del rol
  `superuser` con rechazo de mutaciones.

**Nota de implementación:** el filtro por departamento resuelve el área del
actor, porque `audit_logs` no almacena un departamento propio.

### Épico 18 — Configuración operativa y estado del sistema

- Límite máximo por archivo y verificación real de su alineación con
  `upload_max_filesize` y `post_max_size` de PHP, con aviso cuando no coinciden.
- Catálogo completo de extensiones permitidas y número de tipos MIME validados.
- Disco, driver, visibilidad y ruta raíz configurada, siempre relativa al
  proyecto; nunca se imprime la ruta absoluta del servidor.
- Comprobaciones de que el disco privado está fuera de `public` y de que no
  existe un enlace público de almacenamiento.
- Espacio utilizado separado en activo y papelera, espacio disponible y
  porcentaje ocupado del volumen, obtenidos del sistema de archivos real.
- Retención de papelera, programación y comando de purga, archivos pendientes de
  purga y aviso de que las carpetas se purgan manualmente.
- Clasificaciones disponibles y recordatorio de que publicar exige permiso.
- Estado del API de Accesos con servidor, si la clave está configurada, tiempo
  de espera, intervalo de revalidación y fecha de la última validación de la
  sesión, que es evidencia real de que el API respondió.
- Comprobación en vivo bajo demanda: el panel no lanza peticiones externas al
  cargarse. El resultado se conserva una hora y distingue en línea, con
  incidencias y sin conexión, sin devolver el cuerpo de la respuesta remota.
- La única ruta de escritura de la sección ejecuta esa comprobación y registra
  `admin.settings.connection_checked`; la configuración sigue siendo de solo
  lectura y se cambia por despliegue.
- `AdminSystemStatusService` encapsula límites, almacenamiento, papelera y
  estado del API.
- Se retiraron de `AdminController` los métodos `settings`, `files` y
  `formatBytes`, que habían quedado sin uso al mover cada sección a su propio
  controlador.
- Seis pruebas Feature cubren límites y consumo reales, ausencia de secretos y
  rutas físicas, ausencia de llamadas externas al cargar, comprobación exitosa
  auditada, comprobación fallida sin filtrar detalles y reserva del rol
  `superuser` sin rutas de escritura de configuración.

### Épico 19 — Seguridad y Policies administrativas

- Nuevo middleware `EnsureAdministrativePermission`, alias `admin.permission`,
  que exige el permiso `nube_administracion_administrar` de forma literal en los
  permisos efectivos de la sesión, sin el comodín que sí aplica
  `EnsureAccessPermission`.
- Las siete rutas administrativas de escritura quedan con tres capas
  independientes: rol `superuser`, permiso funcional por middleware y Policy
  administrativa que valida el estado del recurso.
- Si Accesos deja de devolver el permiso, la siguiente revalidación de sesión
  cierra la escritura aunque la copia local del usuario todavía lo conserve.
- Policies administrativas completas: `viewAdministrative`,
  `downloadAdministrative`, `changeVisibilityAdministrative`,
  `deleteAdministrative`, `restoreAdministrative` y `forceDeleteAdministrative`
  en archivos; `viewAdministrative`, `restoreAdministrative` y
  `forceDeleteAdministrative` en carpetas.
- Confirmación en acciones sensibles: modal para enviar a papelera y
  reclasificar, y confirmación por nombre exacto validada en el servidor para
  cualquier eliminación definitiva.
- Auditoría obligatoria comprobada por prueba para consulta de metadatos,
  descarga, reclasificación, envío a papelera, restauración y purga, incluyendo
  actor y dirección IP en cada evento.
- Identificadores inválidos, path traversal y recursos en estado incompatible
  con la operación se resuelven como `404` antes de llegar a la Policy.
- Los errores administrativos devuelven mensajes neutros, sin rutas físicas,
  nombres almacenados ni el cuerpo de la excepción.
- Seis pruebas Feature cubren las tres capas por ruta, la pérdida del permiso en
  sesión, la traza de auditoría de cada mutación, el rechazo de traversal e
  identificadores desconocidos, las operaciones imposibles por estado y los
  mensajes de error sin datos sensibles.

**Discrepancia con la tarjeta:** el tablero nombra el permiso `nube.administrar`.
Se conserva `nube_administracion_administrar`, que es la clave real del catálogo
de Accesos y la única compatible con la convención de guion bajo y prefijo por
recurso definida en `AGENT.md`.

### Épico 20 — Pruebas, estabilización y cierre administrativo (parcial)

- Ocho pruebas Feature nuevas en `AdminStabilizationTest` cierran los huecos de
  cobertura detectados:
  - Fallo del API de Accesos durante la revalidación en cinco secciones
    administrativas: la sesión se cierra y redirige, sin error 500.
  - Fallo del API en una ruta de escritura: no hay mutación ni evento
    administrativo.
  - Descarga administrativa sin copia física: `404` y sin auditar la descarga.
  - Reclasificación y envío a papelera sin copia física: error neutro y registro
    intacto.
  - Restauración sin copia física: el archivo permanece en papelera.
  - Purga con copia física ya ausente: se limpia el registro huérfano.
  - El evento de auditoría sobrevive a la desaparición del recurso descrito.
- `docs/MANUAL_SUPERUSUARIO.md` creado: condiciones de acceso, qué no hace el
  panel, las siete secciones, operaciones sensibles, eventos registrados,
  situaciones frecuentes y límites conocidos.
- `docs/QA_ADMINISTRATIVO.md` creado: 40 casos automatizados mapeados a las seis
  tareas de la tarjeta, comandos de reproducción, regresión, riesgos aceptados y
  la lista explícita de lo que queda pendiente de QA manual.
- Las referencias a pruebas del documento de QA se verificaron automáticamente
  contra el código; las 40 existen.

**Pendiente del épico:** el QA manual con navegador (responsive, modo claro y
oscuro, consola, modales, cargas de 200 MB, pruebas contra el API real y
capturas). Está enumerado caso por caso en `docs/QA_ADMINISTRATIVO.md`.

### Foto de perfil del usuario (ampliación fuera de épicos)

Solicitud posterior al Épico 20: hacer clicable el avatar que acompaña al nombre
y el departamento, y permitir cambiar la foto.

- Nueva columna local `users.avatar_path`, no asignable en masa. El sistema de
  Accesos no gestiona la foto y la sincronización de login no la modifica.
- Nueva vista `/perfil` con la foto actual, el formulario de carga, la opción de
  volver a la predeterminada y los datos de cuenta en solo lectura.
- El avatar es clicable en las cuatro ubicaciones donde aparece: barra lateral
  personal, barra lateral administrativa, encabezado personal y encabezado
  administrativo.
- Foto predeterminada: las **iniciales** del usuario sobre el color
  institucional `#601633`, generadas por `InitialsAvatarGenerator` como SVG
  embebido en un data URI. No requiere ruta adicional, petición extra ni
  servicios externos, y la CSP ya admite `data:` en `img-src`.
- Las iniciales toman la primera letra del nombre y la del apellido; sin
  apellido usa las dos primeras del nombre y, sin datos utilizables, un guion.
  Respeta acentos y descarta símbolos.
- Almacenamiento en el disco privado `nube`, en `perfiles/{user_id}/`, con
  nombre físico aleatorio. No se usa `storage:link` ni ruta pública.
- La imagen se sirve por controlador y sólo al propio usuario; la URL incluye un
  sufijo derivado de la ruta para invalidar la copia del navegador al cambiarla.
- Vista previa inmediata al seleccionar la imagen: se muestra con
  `URL.createObjectURL`, con etiqueta «Vista previa», nombre y peso del archivo,
  botón para descartar la selección y liberación del `objectURL` al descartar o
  abandonar la página.
- El navegador avisa antes de enviar si el archivo excede el límite o no es un
  formato permitido, y deshabilita el botón; la autorización real sigue siendo
  del servidor.
- Validación de imagen, extensión, MIME y tamaño configurable con
  `NUBE_AVATAR_MAX_SIZE_KB` (**10 MB** por omisión), y compensación
  transaccional: si falla el registro se borra la imagen nueva; al reemplazar se
  borra la anterior.
- Auditoría `profile.avatar_updated` y `profile.avatar_removed`.
- `AvatarStorageService` encapsula el guardado, el reemplazo y el borrado.
- Los nueve controladores que construían el avatar con una imagen fija ahora
  usan `User::avatarUrl()`.
- `ProfileAvatarTest` cubre iniciales predeterminadas, SVG autocontenido sin
  referencias remotas, ocho variantes de iniciales (con y sin apellido, acentos,
  símbolos, nombre compuesto y sin datos), enlace desde la navegación, carga
  privada auditada, servicio por controlador, reemplazo con borrado del
  anterior, retorno a las iniciales, rechazo de archivos inválidos, aislamiento
  entre usuarios, exigencia de sesión y supervivencia a la sincronización.

### Recorridos guiados con driver.js (ampliación fuera de épicos)

Botón de ayuda (`#help`) en el encabezado de la nube personal, que abre un menú
con los recorridos guiados disponibles para la página actual.

- El menú es **contextual por página**, no una lista global fija: cada vista
  declara su clave con `<x-layouts.app help-page="...">`, que se refleja como
  `data-help-page` en el `<body>`. `app.js` sólo ofrece los recorridos
  registrados para esa clave.
- Cada paso de un recorrido se filtra en tiempo de ejecución contra el DOM real
  (`element.offsetParent !== null`). Un paso que apunta a un elemento oculto o
  condicionado a permisos (por ejemplo, "Subir archivo" para un usuario de solo
  lectura) se omite en vez de romper el recorrido.
- Ningún recorrido se dispara solo: es enteramente bajo demanda. Se descartó
  el onboarding automático en el primer login para no necesitar una marca de
  "ya lo vio" (columna nueva o `localStorage`); si se agrega más adelante,
  puede convivir con este menú sin conflicto.
- Anclas añadidas como `data-tour="..."` en `dashboard.blade.php` (navegación,
  acciones rápidas, indicadores, archivos recientes) y en
  `folders/index.blade.php` (breadcrumbs, acciones de la ubicación, resumen,
  filtros, contenido).
- El panel del menú (`data-help-menu`, `data-help-menu-panel`) es un
  desplegable propio del proyecto: se cierra con clic afuera, con Escape y
  gestiona `aria-expanded`; no depende de ninguna librería de menús.
- Dos recorridos implementados como ejemplo: **Inicio** y **Explorador**
  (Mis archivos, Mi departamento, Públicos y Papelera comparten la misma
  clave `explorer`). El resto de páginas muestra el mensaje «Todavía no hay un
  recorrido guiado disponible para esta página» en vez de ocultar el botón.
- `GuidedTourTest` protege el contrato entre las vistas y el JavaScript: los
  atributos `data-help-page` y `data-tour` deben seguir presentes para que los
  recorridos definidos en `app.js` encuentren sus objetivos.

**Pendiente:** replicar el botón de ayuda en el layout administrativo
(`components/layouts/admin.blade.php`), que tiene su propio encabezado y hoy no
lo incluye.

### Ayuda puntual junto a controles complejos (ampliación fuera de épicos)

Componente `x-ui.help-tip`: un botón `?` pequeño que abre un popover con una
explicación breve, para controles cuyo comportamiento no es obvio con solo
verlos. Es un mecanismo aparte de los recorridos de driver.js, no una
extensión de ellos.

- **Por qué no usa driver.js:** dos de los cuatro sitios elegidos viven dentro
  de un `x-ui.modal` (`fixed inset-0 z-50` con su propio fondo semitransparente).
  El overlay de pantalla completa de un recorrido de driver.js chocaría con ese
  fondo. Un popover propio, ligero, evita el problema por completo.
- El popover se ancla a la **izquierda** del botón, no centrado, porque el
  panel de `x-ui.modal` usa `overflow-y-auto`, que por especificación CSS
  también recorta el desbordamiento horizontal (`overflow-x` pasa a `auto`
  cuando `overflow-y` no es `visible`). Centrar el popover arriesgaba
  recortarlo cerca de los bordes del modal.
- Manejo por **delegación de eventos** en `app.js` (`closeAllHelpTips`, un
  único listener en `document`), no un listener por instancia, porque puede
  haber muchas copias en una sola página (un modal de purga por cada archivo o
  carpeta en la papelera administrativa).
- Cuatro instancias implementadas, elegidas por explicar un comportamiento no
  obvio y no solo "dónde está el botón":
  - **Alcance de colaboración** (`folders/partials/collaboration-fields.blade.php`):
    diferencia entre "todo el departamento" y "personas específicas" con
    permisos internos propios.
  - **Confirmación por nombre exacto** (`admin/trash.blade.php`, en los dos
    modales de purga): por qué se exige escribir el nombre antes de eliminar
    definitivamente.
  - **Última validación vs. comprobación en vivo** (`admin/settings.blade.php`):
    distingue evidencia pasiva de sesión de una consulta activa al API.
  - **Origen administrativa/de usuario** (`admin/audit.blade.php`): qué
    significa cada valor del filtro de origen en la bitácora.
- `HelpTipTest` verifica las cuatro ubicaciones con el texto real de cada
  explicación, no sólo la presencia del atributo `data-help-tip`.

### Correcciones de UX tras la revisión de todas las vistas (ampliación fuera de épicos)

Aplicación de los hallazgos priorizados en la revisión de UX del 14 de agosto
de 2026. Se excluyó a propósito el hallazgo de mayor esfuerzo (carga por AJAX
de los modales de reclasificación, que hoy repiten el selector completo de
colaboradores por cada archivo o carpeta de la página) por ser un cambio de
arquitectura que merece su propia decisión, no una corrección puntual.

- **Páginas de error con marca propia** (`resources/views/errors/403|404|419|429|500|503.blade.php`,
  vía el nuevo `x-layouts.error`): antes caían en la vista genérica de
  Laravel. El CTA principal distingue sesión autenticada de invitado.
  - Riesgo detectado y corregido antes de publicarse: el botón "volver atrás"
    de 419 usaba `onclick` en línea, que la CSP del proyecto bloquea
    (`script-src` sin `unsafe-inline`); se sustituyó por un enlace normal.
  - Segundo riesgo detectado y corregido: el modo mantenimiento (503)
    intercepta la petición antes de que la sesión arranque, así que `@auth`
    ahí lanzaría `RuntimeException: Session store not set on request`. El
    layout acepta `:requires-session="false"` para omitir esa comprobación;
    503 es la única vista que la usa.
  - Antes de construir esto se verificó empíricamente (no se asumió) si las
    cabeceras de seguridad sobreviven a una respuesta 403/404 real, dado que
    `SecurityHeaders` las escribe después de `$next($request)`. Sí sobreviven;
    no había ninguna brecha.
- **Paginación con la paleta del proyecto** (`resources/views/vendor/pagination/tailwind.blade.php`):
  sustituye el tema gris por defecto de Laravel en las ~10 listas paginadas de
  la aplicación. Se descubre automáticamente por convención de Laravel; no
  requirió publicar nada ni tocar configuración.
- **Filtros colapsables en móvil** (`x-ui.collapsible-filters`, `<details>`
  nativo sin dependencias): antes el panel de filtros se mostraba siempre
  expandido, obligando a desplazarse en móvil antes de ver cualquier
  contenido. Colapsado por defecto; `app.js` lo abre automáticamente en
  escritorio (`>= 1024px`, el mismo punto de quiebre `lg:` que ya usa el resto
  de la interfaz) para no alterar el comportamiento que ya existía ahí.
  Aplicado en el explorador y en Archivos, Papelera, Usuarios, Departamentos y
  Auditoría del panel admin.
- **Acciones directas en el detalle de archivo administrativo**
  (`admin/file-show.blade.php`): antes era de solo lectura; para descargar,
  reclasificar o enviar a papelera había que volver al listado. Ahora ofrece
  las mismas tres acciones que la fila del listado, con los mismos dos
  modales, gestionadas por el mismo permiso `nube_administracion_administrar`.
  El enlace "Volver" se corrigió de `url()->previous()` (podía apuntar a
  cualquier referente) a la ruta fija del listado.
- **Acceso al panel admin desde la barra inferior móvil**
  (`components/navigation/mobile-nav.blade.php`): antes sólo estaba en la
  barra lateral de escritorio y en el menú hamburguesa móvil, no en la barra
  fija inferior que es el patrón de navegación principal en ese tamaño de
  pantalla. Visible únicamente para el rol `superuser`.
- **Tamaños de página unificados**: el explorador personal usaba 10/25/50
  mientras los cuatro listados administrativos comparables (Archivos,
  Papelera, Usuarios, Departamentos) usaban 10/20/50. Se alineó el explorador
  a 10/20/50, tanto en la vista como en `BrowseExplorerRequest`. Auditoría
  conserva 25/50/100 a propósito, por su mayor volumen típico de registros.
- Eliminado `resources/views/welcome.blade.php` (277 líneas): la portada por
  defecto de Laravel, sin ninguna ruta que la sirviera.
- Pruebas nuevas: `CustomErrorPagesTest`, `BrandedPaginationTest`,
  `CollapsibleFiltersTest`, `AdminFileDetailActionsTest` y
  `MobileAdminNavigationTest`.

## Decisiones que deben conservarse

- El sistema de accesos es la fuente oficial de usuarios, departamentos, roles
  y permisos.
- Los roles son informativos para las operaciones funcionales. La excepción
  acotada `superuser` habilita únicamente el panel administrativo de
  consulta.
- La autorización funcional sobre archivos y carpetas se realiza
  exclusivamente con permisos efectivos y Policies.
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
NUBE_AVATAR_MAX_SIZE_KB=10240
NUBE_TRASH_RETENTION_DAYS=30
```

La clave real debe permanecer únicamente en el `.env` local o en el
administrador de secretos del entorno.

## Verificación al cierre

- Pruebas: **216 aprobadas, 1399 aserciones**.
- Laravel Pint: aprobado.
- Compilación de Vite: aprobada.
- Programación diaria: aprobada mediante `php artisan schedule:list`.
- Rutas: aprobadas.
- Compilación de vistas Blade: aprobada.
- `git diff --check`: aprobado.
- `.env.example`: verificado sin clave secreta.
- Limitación: no había un navegador disponible para efectuar la inspección
  visual interactiva, capturar evidencias finales o revisar la consola. El
  renderizado Blade, las rutas, los flujos HTTP, los estados responsive
  declarados, la accesibilidad programática y la compilación frontend sí fueron
  verificados.

Comandos de comprobación:

```powershell
php artisan test
vendor\bin\pint --test
npm run build
php artisan route:list --except-vendor
```

## Punto exacto para retomar

Los **Épicos 06, 07, 08 y 11 a 19** están implementados y en Revisión y QA. El
**Épico 09** está en Terminado. Del **Épico 20** quedan completadas la cobertura
automatizada y la documentación; falta únicamente el QA manual con navegador,
enumerado caso por caso en `docs/QA_ADMINISTRATIVO.md`, que afecta también a los
Épicos 06, 07 y 11 a 19. El **Épico 10 — Preparación de despliegue y entrega del
MVP** permanece en Backlog y es el siguiente trabajo de implementación.

Las migraciones `2026_08_13_000001_add_deleted_by_to_files_and_folders` y
`2026_08_13_000002_add_avatar_path_to_users_table` ya fueron aplicadas en la base
local; cualquier otro entorno debe ejecutar `php artisan migrate` antes de abrir
la papelera global o el perfil de usuario.

Antes de comenzar, revisar también `AGENT.md`,
`Plan_de_Desarrollo_por_Fases_Nube_Municipal.md`,
`Base_de_Datos_Nube_Municipal.md` y `Propuesta_MVP_Nube_Municipal.md`.
