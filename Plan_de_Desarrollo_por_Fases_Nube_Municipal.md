# Plan de desarrollo por fases
## Nube Municipal — MVP

**Proyecto:** Plataforma interna de almacenamiento de archivos  
**Tecnologías:** Laravel 12, Blade, Tailwind CSS, JavaScript nativo y base de datos relacional  
**Tipo de almacenamiento:** Local y privado mediante Laravel Storage  
**Autenticación:** Sistema de accesos externo  
**Fecha de inicio:** 23 de julio de 2026  
**Fecha objetivo del MVP:** 31 de julio de 2026

---

## 1. Objetivo general

Desarrollar un MVP funcional de **Nube Municipal**, una plataforma interna similar a OneDrive que permita a los usuarios almacenar, organizar, descargar y administrar archivos privados, colaborativos y públicos internos.

La plataforma utilizará el sistema de accesos existente como fuente oficial de usuarios, departamentos, roles y permisos. Laravel conservará una copia local de esa información para facilitar las relaciones, la autorización, las consultas y la auditoría.

El MVP deberá garantizar que:

- Los archivos privados solo sean accesibles por su propietario.
- Los archivos colaborativos solo sean accesibles por usuarios del mismo departamento.
- Los archivos públicos internos sean accesibles por cualquier usuario autenticado.
- Ningún archivo sea accesible directamente mediante una URL pública.
- Todas las operaciones sensibles pasen por controladores y Policies de Laravel.
- Las operaciones críticas queden registradas en auditoría.

---

## 2. Principios de implementación

El desarrollo seguirá los siguientes principios:

- Mantener el alcance limitado al MVP.
- Separar autenticación, reglas de negocio, almacenamiento y presentación.
- Utilizar Policies para autorizar acciones sobre carpetas y archivos.
- Utilizar Form Requests para validar entradas.
- Guardar los archivos fuera del directorio `public`.
- Usar UUID para carpetas y archivos.
- Mantener consistencia entre base de datos y almacenamiento físico.
- Implementar eliminación lógica para archivos y carpetas.
- Registrar eventos importantes en `audit_logs`.
- Crear componentes Blade reutilizables.
- Mantener la interfaz alineada con los mockups aprobados en Figma.

---

# 3. Fases de desarrollo

## Fase 1. Preparación técnica y estructura del proyecto

### Objetivo

Dejar lista la base técnica del proyecto para comenzar el desarrollo funcional sin introducir cambios estructurales importantes durante las siguientes fases.

### Actividades

#### Proyecto y entorno

- Crear el proyecto con Laravel 12.
- Configurar el archivo `.env`.
- Configurar la conexión a MySQL o PostgreSQL.
- Instalar y configurar Tailwind CSS.
- Configurar Vite para compilar los recursos frontend.
- Verificar versiones de PHP, Composer y Node.js.
- Configurar la zona horaria de la aplicación.
- Configurar idioma y localización.

#### Repositorio

- Crear el repositorio Git.
- Agregar un archivo `.gitignore` adecuado.
- Definir estrategia de ramas:
  - `main`: versión estable.
  - `develop`: integración.
  - `feature/*`: funcionalidades.
  - `fix/*`: correcciones.
- Crear el primer commit funcional.

#### Estructura de aplicación

Preparar los directorios y clases iniciales para:

- Controladores.
- Servicios.
- Middleware.
- Policies.
- Form Requests.
- Enums o constantes.
- Vistas Blade.
- Componentes Blade.

#### Almacenamiento

Configurar un disco privado llamado `nube`:

```php
'nube' => [
    'driver' => 'local',
    'root' => storage_path('app/nube'),
    'visibility' => 'private',
    'throw' => true,
],
```

Crear la estructura física inicial:

```text
storage/app/nube/
├── departamentos/
├── papelera/
└── temporales/
```

#### Interfaz base

- Crear el layout principal.
- Implementar modos claro y oscuro con preferencia persistente.
- Implementar la barra lateral.
- Implementar el encabezado.
- Crear el contenedor principal.
- Preparar componentes reutilizables:
  - Botones.
  - Inputs.
  - Selectores.
  - Alertas.
  - Modales.
  - Tablas o listas.
  - Breadcrumbs.
  - Menús contextuales.

### Entregables

- Proyecto Laravel ejecutable.
- Base de datos conectada.
- Tailwind CSS funcionando.
- Vite configurado.
- Disco privado `nube` configurado.
- Layout principal implementado.
- Repositorio Git inicializado.

### Criterio de aceptación

El proyecto debe ejecutarse sin errores, mostrar el layout principal y permitir operaciones de lectura y escritura en el almacenamiento privado.

---

## Fase 2. Base de datos y modelos Eloquent

### Objetivo

Implementar el modelo de datos aprobado y preparar las relaciones necesarias para usuarios, departamentos, permisos, carpetas, archivos y auditoría.

### Tablas

Crear las migraciones de:

- `departments`.
- `users`.
- `roles`.
- `user_roles`.
- `permissions`.
- `user_permissions`.
- `folders`.
- `files`.
- `audit_logs`.

### Actividades

#### Migraciones

- Utilizar `BIGINT UNSIGNED` para usuarios y departamentos.
- Utilizar UUID para carpetas y archivos.
- Crear las llaves foráneas.
- Configurar restricciones de eliminación.
- Crear índices para consultas frecuentes.
- Agregar `deleted_at` en `folders` y `files`.
- Agregar timestamps.
- Configurar campos JSON en auditoría.
- Agregar restricciones de unicidad.

#### Modelos

Crear los modelos:

- `Department`.
- `User`.
- `Role`.
- `Permission`.
- `Folder`.
- `File`.
- `AuditLog`.

#### Relaciones Eloquent

Implementar:

```text
Department hasMany Users
Department hasMany Folders
Department hasMany Files
Department belongsTo Parent Department
Department hasMany Child Departments

User belongsTo Department
User belongsToMany Roles
User belongsToMany Permissions
User hasMany Folders
User hasMany Files
User hasMany AuditLogs

Folder belongsTo User
Folder belongsTo Department
Folder belongsTo Parent Folder
Folder hasMany Child Folders
Folder hasMany Files

File belongsTo Folder
File belongsTo User
File belongsTo Department
```

#### Catálogos y valores controlados

Crear un enum o clase de constantes para visibilidad:

```text
private
collaborative
public
```

No crear un seeder de producción con un catálogo fijo de permisos. El sistema
de accesos es la única fuente oficial. Los seeders de permisos se limitarán a
datos representativos para los entornos `local` y `testing`.

#### Datos de prueba

- Crear factories para departamentos.
- Crear factories para usuarios.
- Crear factories para carpetas.
- Crear factories para archivos.
- Crear seeders de datos demostrativos.

### Entregables

- Migraciones completas.
- Modelos Eloquent.
- Relaciones funcionales.
- Factories.
- Seeders de datos demostrativos.
- Estructura local preparada para sincronizar permisos dinámicamente.

### Criterio de aceptación

Las migraciones deben ejecutarse correctamente desde una base de datos vacía. Los modelos deben permitir crear y relacionar usuarios, departamentos, carpetas, archivos, roles y permisos.

---

## Fase 3. Integración con el sistema de accesos

### Objetivo

Permitir que los usuarios inicien sesión mediante el sistema central y sincronizar localmente su usuario, departamento, roles y permisos.

### Actividades

#### Configuración

Agregar al `.env`:

```env
ACCESS_API_URL=https://accesos.digitalneza.com
ACCESS_SYSTEM_KEY=clave_del_sistema
ACCESS_TIMEOUT=10
```

Crear un archivo de configuración dedicado para el API.

#### Servicio de integración

Crear `AccessApiService` con métodos para:

- Iniciar sesión.
- Consultar al usuario autenticado.
- Cerrar sesión.
- Consultar departamentos.
- Consultar usuarios asignados al sistema.
- Manejar errores del API.
- Manejar tiempos de espera.

#### Inicio de sesión

- Crear formulario Blade.
- Validar correo y contraseña.
- Consumir `POST /api/auth/login`.
- Enviar `system_key`.
- Guardar el token en la sesión del servidor.
- No almacenar contraseñas.

#### Sincronización local

Después de un login exitoso:

1. Crear o actualizar el departamento.
2. Crear o actualizar el usuario.
3. Crear o actualizar roles.
4. Sincronizar `user_roles`.
5. Crear o actualizar dinámicamente cada permiso efectivo recibido del API.
6. Sincronizar `user_permissions` con la lista exacta recibida.
7. Actualizar `last_login_at`.
8. Actualizar `last_synced_at`.

#### Modelo operativo de roles y permisos

El sistema de accesos devuelve los permisos como una lista plana de claves:

```json
{
  "roles": [
    "nube_colaborador"
  ],
  "permissions": [
    "nube_inicio_ver",
    "nube_mis_archivos_ver",
    "nube_mis_archivos_subir"
  ]
}
```

Reglas obligatorias:

- Los roles son informativos y no conceden capacidades funcionales por sí
  mismos. La excepción acotada es `superuser`, que habilita el acceso al
  panel administrativo de consulta, sin conceder operaciones sobre recursos.
- Los permisos efectivos asignados al usuario son la única fuente de
  autorización funcional.
- Cada permiso debe tener una clave globalmente única porque el API no utiliza
  el recurso para distinguir permisos con el mismo nombre.
- Las claves utilizarán guion bajo y estarán prefijadas con la clave del
  recurso.
- Laravel no autorizará acciones por el nombre de un rol.
- Los roles recibidos se sincronizarán en `user_roles` para perfil, consulta y
  auditoría.
- La lista efectiva recibida del API se sincronizará exactamente en
  `user_permissions`.
- En cada sincronización se eliminarán las asignaciones locales que el API ya
  no devuelva.
- No se eliminarán globalmente registros de `permissions` durante el login,
  porque pueden continuar asignados a otros usuarios.
- No existirá un catálogo fijo de permisos en seeders de producción.
- Los seeders locales contendrán únicamente permisos representativos para
  demostración y pruebas; no deben copiar el catálogo completo de Accesos.
- Los permisos decidirán si una acción está disponible y las Policies
  comprobarán propietario, departamento, visibilidad y estado del recurso.
- Ocultar una sección o botón no sustituye la validación en middleware,
  controlador y Policy.

Roles informativos recomendados:

```text
nube_consulta
nube_colaborador
nube_publicador
nube_administrador
```

Uso descriptivo:

- `nube_consulta`: usuarios orientados a consulta y descarga.
- `nube_colaborador`: usuarios que administran sus archivos privados y
  consultan contenido departamental y público.
- `nube_publicador`: usuarios que también pueden crear y publicar contenido
  departamental o público.
- `nube_administrador`: responsables operativos de la plataforma.

Aunque un usuario tenga el rol `nube_administrador`, deberá recibir
explícitamente todos los permisos que necesite.

#### Catálogo de permisos por recurso

Este catálogo define el contrato funcional esperado y se administra en el
sistema de accesos. Nube Municipal no lo duplicará en un seeder de
producción; registrará localmente las claves conforme las reciba del API.

Recurso `nube_inicio`:

```text
nube_inicio_ver
```

`nube_inicio_ver` es obligatorio para iniciar sesión y acceder a la
aplicación.

Recurso `nube_mis_archivos`:

```text
nube_mis_archivos_ver
nube_mis_archivos_crear_carpeta
nube_mis_archivos_subir
nube_mis_archivos_descargar
nube_mis_archivos_renombrar
nube_mis_archivos_mover
nube_mis_archivos_eliminar
nube_mis_archivos_publicar
```

Recurso `nube_departamento`:

```text
nube_departamento_ver
nube_departamento_crear_carpeta
nube_departamento_subir
nube_departamento_descargar
nube_departamento_renombrar
nube_departamento_mover
nube_departamento_eliminar
nube_departamento_publicar
```

Recurso `nube_publicos`:

```text
nube_publicos_ver
nube_publicos_crear_carpeta
nube_publicos_subir
nube_publicos_descargar
nube_publicos_renombrar
nube_publicos_mover
nube_publicos_eliminar
nube_publicos_publicar
```

Recurso `nube_papelera`:

```text
nube_papelera_ver
nube_papelera_restaurar
```

La eliminación física definitiva no forma parte del alcance inicial del MVP.

Recurso `nube_administracion`:

```text
nube_administracion_administrar
```

El catálogo operativo administrado en Accesos contiene 28 permisos únicos. El permiso
`nube_administracion_administrar` será el indicador de capacidad
administrativa para las Policies; el rol informativo no reemplaza esta
comprobación.

#### Sesión

- Implementar middleware para validar sesión local.
- Consumir `GET /api/auth/me` cuando sea necesario.
- Detectar token vencido.
- Limpiar la sesión cuando el token no sea válido.
- Verificar el permiso `nube_inicio_ver`.

#### Cierre de sesión

- Consumir `POST /api/auth/logout`.
- Revocar la sesión central.
- Destruir la sesión local.
- Redirigir al login.

#### Manejo de errores

Contemplar:

- `401`: credenciales o token inválido.
- `403`: usuario sin acceso.
- `404`: endpoint o recurso no encontrado.
- `422`: errores de validación.
- `500`: error del sistema central.
- Tiempo de espera agotado.
- API no disponible.

### Entregables

- Login funcional.
- Logout funcional.
- Sincronización de usuario y departamento.
- Sincronización de roles y permisos.
- Middleware de autenticación.
- Manejo de errores.

### Criterio de aceptación

Un usuario válido debe iniciar sesión, quedar registrado o actualizado
localmente, conservar su departamento y roles informativos, sincronizar
exactamente sus permisos efectivos y acceder únicamente cuando tenga
`nube_inicio_ver`.

### Estado de implementación — 24 de julio de 2026

Completado y verificado:

- Cliente tipado para todos los endpoints documentados del API de accesos.
- Login responsive basado en los estados de la sección `32:2` de Figma.
- Token Bearer almacenado exclusivamente en la sesión del servidor.
- Sincronización transaccional de departamento, usuario, roles y permisos.
- Reemplazo exacto de permisos del usuario sin borrar el catálogo compartido.
- Validación obligatoria de `nube_inicio_ver`.
- Middleware con revalidación periódica mediante `/api/auth/me`.
- Logout central y local, recuperación de contraseña y auditoría de acceso.
- Estados de credenciales inválidas, cuenta inactiva, falta de permiso,
  validación, conexión y carga.
- Pruebas Feature de login, sincronización, permiso faltante, token vencido,
  API no disponible y logout.

---

## Fase 4. Explorador y gestión de carpetas privadas

### Objetivo

Construir el explorador principal y permitir que cada usuario administre su estructura privada de carpetas.

### Secciones principales

- Mis archivos.
- Mi departamento.
- Públicos.
- Papelera.

### Actividades

#### Navegación

- Mostrar carpetas y archivos de la ubicación actual.
- Implementar navegación por subcarpetas.
- Implementar breadcrumbs.
- Mostrar ruta lógica.
- Implementar regreso a la carpeta anterior.

#### Crear carpetas

- Crear carpetas privadas.
- Crear subcarpetas.
- Validar nombre obligatorio.
- Validar longitud.
- Evitar nombres duplicados en el mismo nivel.
- Asociar propietario y departamento.

#### Renombrar carpetas

- Permitir únicamente al propietario.
- Validar duplicados.
- Actualizar `path_cache` cuando corresponda.
- Registrar auditoría.

#### Eliminar carpetas

- Permitir eliminación lógica.
- Restringir inicialmente la eliminación a carpetas vacías.
- Evitar eliminar carpetas ajenas.
- Registrar auditoría.

#### Seguridad

Crear:

- `FolderController`.
- `FolderPolicy`.
- Form Requests para crear y renombrar.
- Servicio para rutas lógicas.

La autorización no debe depender únicamente de la ruta recibida.

### Reglas

- Un usuario solo puede modificar sus carpetas privadas.
- Las carpetas deben pertenecer al departamento del usuario.
- Una subcarpeta debe conservar la visibilidad de su estructura padre.
- No se puede crear contenido dentro de una carpeta eliminada.
- No se aceptan rutas físicas enviadas por el navegador.

### Entregables

- Explorador de carpetas.
- Navegación jerárquica.
- Breadcrumbs.
- Creación de carpetas.
- Renombrado.
- Eliminación lógica.
- Policies.
- Auditoría.

### Criterio de aceptación

El usuario debe poder crear y navegar carpetas privadas sin acceder o modificar carpetas pertenecientes a otro usuario.

---

## Fase 5. Gestión de archivos privados

### Objetivo

Implementar el ciclo completo de carga, consulta, descarga, renombrado, movimiento y eliminación de archivos privados.

### Actividades

#### Carga

- Crear formulario o modal de carga.
- Seleccionar carpeta destino.
- Validar archivo obligatorio.
- Validar tamaño máximo.
- Validar extensión.
- Validar MIME.
- Validar propiedad de la carpeta.
- Generar UUID.
- Generar nombre físico seguro.
- Mantener nombre original y nombre visible.
- Calcular checksum SHA-256 si se mantiene dentro del alcance.

#### Tipos permitidos

- PDF.
- DOC y DOCX.
- XLS y XLSX.
- PPT y PPTX.
- TXT.
- CSV.
- JPG, JPEG y PNG.
- ZIP.

#### Tamaño máximo

Configurar inicialmente:

```text
200 MB por archivo
```

El límite debe coincidir en:

- Laravel.
- PHP.
- Nginx o Apache.

#### Guardado

1. Validar la solicitud.
2. Construir la ruta relativa.
3. Guardar el archivo físico.
4. Registrar metadatos en `files`.
5. Si falla la base de datos, eliminar el archivo físico.
6. Si falla el almacenamiento, no crear el registro.

#### Descarga

- Toda descarga debe pasar por un controlador.
- Aplicar `FilePolicy`.
- Verificar que el archivo exista físicamente.
- Registrar la descarga.
- Mantener el nombre visible para el usuario.

#### Renombrado

- Modificar `display_name`.
- Mantener el nombre físico.
- Validar permisos.
- Evitar nombres inválidos.
- Registrar auditoría.

#### Movimiento

- Permitir mover archivos entre carpetas privadas propias.
- Validar carpeta destino.
- Actualizar metadatos.
- Actualizar ruta física cuando corresponda.
- Registrar auditoría.

#### Eliminación y restauración

- Aplicar eliminación lógica.
- Evitar descargas de archivos eliminados.
- Mostrar elementos en Papelera.
- Permitir restauración.
- Mostrar que los archivos se eliminan definitivamente después de 30 días.
- Permitir eliminación permanente manual con confirmación SweetAlert2.
- Ejecutar diariamente una purga de archivos cuyo plazo haya vencido.
- Registrar auditoría.

#### Clases principales

- `FileController`.
- `FileStorageService`.
- `FilePolicy`.
- Form Requests.

### Entregables

- Carga de archivos.
- Listado.
- Descarga segura.
- Renombrado.
- Movimiento.
- Eliminación lógica.
- Papelera.
- Restauración.
- Auditoría.

### Criterio de aceptación

El propietario debe poder administrar sus archivos privados. Ningún usuario debe poder descargar un archivo privado ajeno modificando manualmente la URL.

---

## Fase 6. Archivos colaborativos y públicos internos

### Objetivo

Implementar los tres niveles de visibilidad y sus reglas de autorización.

### Clasificaciones

```text
private
collaborative
public
```

### Reglas de archivos privados

- Solo el propietario puede visualizarlos.
- Solo el propietario puede descargarlos.
- Solo el propietario puede modificarlos.
- Solo el propietario puede eliminarlos.

### Reglas de archivos colaborativos

- Pueden compartirse con todo el departamento o con personas específicas del
  mismo departamento.
- En alcance de departamento, los usuarios pueden visualizar y descargar; solo
  el propietario o `admin_area` dentro de su alcance puede administrar.
- En alcance seleccionado, cada colaborador recibe permisos internos por
  recurso para ver, descargar, renombrar, mover y eliminar. La acción requiere
  también el permiso funcional correspondiente recibido del API.
- Los permisos internos de compartición se almacenan exclusivamente en Nube
  Municipal y no amplían el catálogo del sistema de accesos.
- Usuarios de otros departamentos no pueden acceder.

### Reglas de archivos públicos internos

- Cualquier usuario autenticado puede visualizarlos.
- Cualquier usuario autenticado puede descargarlos.
- Solo el propietario o un administrador puede modificarlos.
- No deben ser accesibles sin autenticación.

### Actividades

- Implementar selector de clasificación durante la carga.
- Implementar cambio de visibilidad.
- Verificar el permiso de publicación correspondiente al recurso:
  `nube_mis_archivos_publicar`, `nube_departamento_publicar` o
  `nube_publicos_publicar`.
- Crear filtros por visibilidad.
- Limitar consultas colaborativas al departamento actual.
- Separar rutas físicas por visibilidad.
- Actualizar Policies.
- Registrar cambios de visibilidad.
- Crear carpetas privadas, colaborativas y públicas.
- Permitir que la visibilidad de una carpeta y sus contenidos sea independiente.
- Listar personas activas del mismo departamento al seleccionar colaboración
  específica.
- Permitir configurar por persona los permisos internos de ver, descargar,
  renombrar, mover y eliminar, con herencia inicial de carpeta a archivos
  nuevos.

### Rutas conceptuales

#### Privados

```text
storage/app/nube/departamentos/{department_id}/usuarios/{user_id}/privados/
```

#### Colaborativos

```text
storage/app/nube/departamentos/{department_id}/colaborativos/
```

#### Públicos internos

```text
storage/app/nube/departamentos/{department_id}/publicos/
```

### Entregables

- Vista de archivos privados.
- Vista de archivos colaborativos.
- Vista de archivos públicos internos.
- Cambio de visibilidad.
- Policies completas.
- Restricciones por departamento.

### Criterio de aceptación

Las pruebas deben demostrar que:

- Un archivo privado solo es accesible por su propietario.
- Un archivo colaborativo solo es accesible por usuarios del mismo departamento.
- Un archivo público interno es accesible por cualquier usuario autenticado.
- Ningún archivo puede descargarse directamente desde el servidor web.

---

## Fase 7. Implementación visual desde Figma

### Objetivo

Convertir los mockups aprobados en vistas funcionales con Blade y Tailwind CSS.

### Identidad visual

- Principal: `#601633`.
- Secundario sutil: `#BE985C`.
- Blanco.
- Negro.
- Estética moderna inspirada en Liquid Glass.
- Tema oscuro accesible que conserve la identidad visual institucional.

### Actividades

#### Navegación

- Barra lateral.
- Encabezado.
- Perfil del usuario.
- Departamento actual.
- Navegación entre secciones.

#### Explorador

- Vista tipo lista.
- Carpetas y archivos.
- Iconos por tipo de archivo.
- Tamaño.
- Propietario.
- Fecha.
- Visibilidad.
- Menú de acciones.

#### Componentes

- Modal de carga.
- Modal para crear carpeta.
- Modal para renombrar.
- Modal para mover.
- Confirmación de eliminación.
- Alertas de éxito y error.
- Estados vacíos.
- Indicadores de carga.

#### Experiencia de usuario

- Estados `hover`.
- Estados `focus`.
- Estados `active`.
- Estados `disabled`.
- Mensajes de validación.
- Diseño adaptable.
- Paginación.
- Filtros básicos.
- Ordenamiento por nombre, fecha o tamaño.

### Entregables

- Pantallas implementadas.
- Componentes Blade reutilizables.
- Diseño adaptable.
- Estados visuales completos.
- Interfaz alineada con Figma.

### Criterio de aceptación

Todas las operaciones principales deben poder realizarse desde la interfaz sin introducir rutas manualmente.

---

## Fase 8. Seguridad y auditoría

### Objetivo

Garantizar que todas las operaciones sensibles estén protegidas y que las acciones críticas queden registradas.

### Eventos de auditoría

Registrar:

```text
auth.login
auth.logout
folder.created
folder.renamed
folder.deleted
file.uploaded
file.downloaded
file.renamed
file.moved
file.deleted
file.restored
file.visibility_changed
```

### Actividades

#### Autorización

- Revisar todas las Policies.
- Revisar middleware de autenticación.
- Verificar permisos centrales.
- Validar propiedad y departamento.

#### Protección de archivos

- Mantener archivos fuera de `public`.
- No utilizar `storage:link` para el disco privado.
- Utilizar nombres físicos aleatorios.
- Evitar path traversal.
- Rechazar rutas manipuladas.
- Verificar existencia física antes de descargar.

#### Validación

- Validar extensión y MIME.
- Sanitizar nombres visibles.
- Limitar tamaño.
- Proteger asignación masiva.
- Validar UUID.
- Aplicar CSRF.

#### Manejo de errores

- Crear respuestas amigables.
- Ocultar detalles técnicos en producción.
- Registrar excepciones.
- Manejar archivos faltantes.
- Manejar inconsistencias de almacenamiento.

### Entregables

- Policies revisadas.
- Middleware revisado.
- Auditoría completa.
- Manejo seguro de errores.
- Revisión de seguridad.

### Criterio de aceptación

Toda descarga, modificación, movimiento, eliminación o cambio de visibilidad debe pasar por autorización y quedar registrada cuando corresponda.

---

## Fase 9. Pruebas funcionales y automatizadas

### Objetivo

Comprobar que autenticación, almacenamiento, navegación, visibilidad y permisos funcionan de acuerdo con el alcance del MVP.

### Pruebas de autenticación

- Login exitoso.
- Login inválido.
- Usuario sin permiso de acceso.
- Token vencido.
- API no disponible.
- Logout.

### Pruebas de sincronización

- Crear usuario local.
- Actualizar usuario existente.
- Crear departamento.
- Actualizar departamento.
- Cambiar departamento del usuario.
- Sincronizar roles.
- Sincronizar permisos.

### Pruebas de carpetas

- Crear carpeta.
- Crear subcarpeta.
- Renombrar.
- Evitar duplicados.
- Eliminar carpeta vacía.
- Rechazar eliminación de carpeta con contenido.
- Bloquear acceso a carpetas ajenas.

### Pruebas de archivos

- Cargar archivo válido.
- Rechazar extensión no permitida.
- Rechazar MIME inválido.
- Rechazar archivo demasiado grande.
- Descargar archivo.
- Renombrar archivo.
- Mover archivo.
- Eliminar archivo.
- Restaurar archivo.
- Bloquear acceso directo.

### Pruebas de visibilidad

- Privado accesible por propietario.
- Privado rechazado para terceros.
- Colaborativo accesible en el mismo departamento.
- Colaborativo rechazado en otro departamento.
- Público accesible para usuario autenticado.
- Público rechazado sin autenticación.

### Pruebas de consistencia

- Registro sin archivo físico.
- Archivo físico sin registro.
- Error durante la carga.
- Error al mover.
- Error al eliminar.
- Restauración.

### Tipos de prueba

- Pruebas Feature para flujos principales.
- Pruebas Unit para servicios críticos.
- Pruebas manuales de interfaz.
- Pruebas de permisos.

### Entregables

- Suite de pruebas.
- Matriz de pruebas.
- Lista de errores encontrados.
- Evidencia de correcciones.

### Criterio de aceptación

Todos los criterios de aceptación del MVP deben pasar antes del despliegue.

---

## Fase 10. Despliegue, documentación y entrega

### Objetivo

Preparar una versión estable, desplegable y documentada.

### Actividades

#### Configuración del servidor

- Configurar PHP.
- Instalar dependencias con Composer.
- Configurar base de datos.
- Configurar Nginx o Apache.
- Compilar recursos frontend.
- Configurar permisos de `storage`.
- Configurar permisos de `bootstrap/cache`.
- Configurar variables de entorno.

#### Límites de carga

Revisar:

```text
upload_max_filesize
post_max_size
client_max_body_size
```

#### Producción

- Ejecutar migraciones.
- Ejecutar seeders necesarios.
- Configurar caché.
- Configurar logs.
- Desactivar modo debug.
- Verificar almacenamiento persistente.
- Verificar espacio disponible.

#### Respaldo

- Definir respaldo de base de datos.
- Definir respaldo de archivos.
- Documentar restauración.
- Verificar permisos de lectura y escritura.

#### Documentación

Preparar:

- `.env.example`.
- Manual de instalación.
- Manual básico de usuario.
- Lista de funciones implementadas.
- Lista de pendientes.
- Procedimiento de respaldo.
- Procedimiento de restauración.
- Datos de demostración.
- Etiqueta de versión del MVP.

### Entregables

- Código fuente.
- Migraciones.
- Seeders.
- MVP desplegado.
- Manual de instalación.
- Manual de usuario.
- Configuración de producción.
- Lista de mejoras posteriores.

### Criterio de aceptación

La plataforma debe poder instalarse desde cero siguiendo la documentación y completar los flujos principales sin errores.

---

## Fase 11. Acceso y navegación del superusuario

### Objetivo

Proporcionar una sección administrativa independiente y de consulta para las
personas que reciban desde el sistema de Accesos el rol `superuser`.

### Actividades

- Crear rutas `/admin` protegidas por sesión válida y middleware de rol.
- Responder `403` a usuarios autenticados sin el rol `superuser`.
- Crear navegación administrativa para Resumen, Archivos, Departamentos,
  Usuarios, Papelera, Auditoría y Configuración.
- Mostrar indicadores y listados globales sin exponer rutas físicas, tokens,
  claves o contraseñas.
- Permitir alternar entre el panel administrativo y la nube personal.
- Mantener las operaciones sobre archivos y carpetas sujetas a los permisos
  efectivos y Policies existentes.

### Entregables

- Middleware `superuser`.
- Layout y navegación administrativa responsive.
- Panel global de consulta con siete secciones.
- Pruebas Feature de acceso, navegación y protección de datos sensibles.

### Criterio de aceptación

Sólo un usuario autenticado con el rol `superuser` puede abrir `/admin`; el
resto recibe `403`. El superusuario puede recorrer todas las secciones y volver
a su nube personal sin adquirir permisos funcionales adicionales.

### Estado de implementación — 28 de julio de 2026

Implementado y cubierto por pruebas automatizadas. La revisión visual
interactiva queda pendiente hasta disponer de un navegador conectado.

---

## Fase 12. Dashboard administrativo

### Objetivo

Presentar al rol `superuser` una vista global del estado y consumo de la
plataforma usando exclusivamente datos reales.

### Actividades

- Mostrar totales de archivos, carpetas, usuarios y departamentos.
- Separar archivos y carpetas activos de los elementos en papelera.
- Calcular el espacio de archivos activos, eliminados y total retenido.
- Distribuir archivos privados, colaborativos y públicos, distinguiendo activos
  y eliminados.
- Mostrar la actividad reciente de todo el sistema.
- Ordenar los cinco departamentos y usuarios con mayor consumo, incluyendo el
  espacio activo y el espacio pendiente de purga.
- Formatear bytes en unidades legibles desde B hasta TB.
- Contemplar estados vacíos cuando todavía no existan archivos.

### Entregables

- Servicio de consultas y agregados administrativos.
- Dashboard responsive con indicadores, desglose y rankings.
- Pruebas Feature con datos activos, eliminados y estados vacíos.

### Criterio de aceptación

Los indicadores deben corresponder a los datos persistidos, separar
correctamente elementos activos y eliminados, ordenar el consumo real y mostrar
unidades legibles sin exponer rutas físicas.

### Estado de implementación — 28 de julio de 2026

Implementado y cubierto por pruebas automatizadas. La revisión visual
interactiva queda pendiente hasta disponer de un navegador conectado.

---

## Fase 13. Explorador global de archivos

### Objetivo

Permitir que el rol `superuser` consulte los metadatos de todos los archivos y,
cuando también tenga el permiso funcional `nube_administracion_administrar`,
realice operaciones administrativas auditadas.

### Actividades

- Crear un listado global paginado con filtros por nombre, departamento,
  usuario, clasificación, tipo, fecha de carga y estado.
- Mostrar metadatos operativos sin revelar ruta física, nombre almacenado ni
  checksum.
- Descargar archivos mediante controlador, autorización y disco privado.
- Cambiar la clasificación manteniendo la consistencia entre metadatos y
  almacenamiento físico.
- Al seleccionar la clasificación colaborativa, permitir acceso a todo el
  departamento propietario o a personas activas específicas de esa área, con
  permisos internos configurables; permitir actualizar este alcance sin mover
  el archivo de su carpeta actual.
- Enviar archivos activos a la papelera con confirmación explícita.
- Mantener la consulta disponible al rol `superuser`, pero exigir además el
  permiso `nube_administracion_administrar` para las mutaciones y descargas.
- Registrar las consultas y operaciones como eventos `admin.file.*`.
- Cubrir estados con resultados, sin resultados, activos y en papelera.

### Entregables

- Controlador y Form Requests administrativos separados.
- Policies específicas para consulta y operación global.
- Explorador responsive, detalle de metadatos y confirmaciones destructivas.
- Pruebas Feature de filtros, autorización, privacidad, almacenamiento y
  auditoría.

### Criterio de aceptación

El superusuario puede localizar y consultar archivos globalmente sin exponer
datos del almacenamiento. Las operaciones requieren permiso funcional, pasan
por Policies, conservan el archivo privado y generan trazabilidad administrativa.

### Estado de implementación — 11 de agosto de 2026

Implementado y cubierto por pruebas automatizadas. La revisión visual
interactiva en escritorio, tableta y móvil queda pendiente.

---

## Fase 14. Administración de departamentos

### Objetivo

Supervisar el estado, sincronización y consumo de la nube por departamento sin
crear ni modificar localmente la estructura proveniente de Accesos.

### Actividades

- Listar departamentos sincronizados con búsqueda y filtro de estado.
- Mostrar usuarios activos y totales, archivos, carpetas, papelera y
  almacenamiento por departamento.
- Presentar estado y fecha de última sincronización.
- Crear un detalle con identidad externa, jerarquía y áreas dependientes.
- Listar usuarios relacionados y archivos colaborativos o públicos activos.
- Mostrar la actividad reciente realizada por usuarios o sobre recursos del
  departamento.
- Navegar al inventario global de archivos y al listado de usuarios conservando
  el filtro departamental.
- Mantener únicamente rutas GET para departamentos; Accesos sigue siendo la
  fuente oficial de creación y edición.

### Entregables

- Servicio administrativo de agregados y relaciones departamentales.
- Listado paginado y detalle responsive de cada departamento.
- Navegación relacionada hacia usuarios, archivos y metadatos.
- Pruebas Feature de métricas, filtros, privacidad y modo de solo consulta.

### Criterio de aceptación

El superusuario puede consultar el estado, sincronización, consumo, usuarios,
archivos compartidos y actividad de cada departamento sin exponer rutas físicas
ni disponer de operaciones locales para crear o editar áreas.

### Estado de implementación — 11 de agosto de 2026

Implementado y cubierto por pruebas automatizadas. La revisión visual
interactiva y el cambio de estado de la tarjeta en Trello quedan pendientes.

---

# 4. Orden recomendado de ejecución

```text
1. Preparación técnica
2. Base de datos
3. Integración con accesos
4. Carpetas privadas
5. Archivos privados
6. Archivos colaborativos y públicos
7. Integración visual
8. Seguridad y auditoría
9. Pruebas
10. Despliegue y documentación
11. Acceso y navegación del superusuario
12. Dashboard administrativo
13. Explorador global de archivos
14. Administración de departamentos
```

La implementación visual puede avanzar parcialmente desde la primera fase, pero las acciones de la interfaz solo deben conectarse cuando las reglas de negocio y autorización correspondientes estén listas.

---

# 5. Priorización del MVP

## Prioridad crítica

- Integración con el sistema de accesos.
- Sincronización de usuario y departamento.
- Base de datos.
- Carpetas privadas.
- Carga de archivos.
- Descarga segura.
- Eliminación lógica.
- Policies.
- Archivos colaborativos.
- Archivos públicos internos.
- Almacenamiento privado.
- Auditoría básica.

## Prioridad alta

- Renombrado.
- Movimiento de archivos.
- Papelera.
- Restauración.
- Indicadores de almacenamiento.
- Diseño adaptable.
- Manejo visual de errores.
- Filtros y ordenamiento.

## Fuera del MVP

- Compartición con usuarios de otros departamentos.
- Historial de versiones.
- Vista previa avanzada.
- Miniaturas.
- Antivirus.
- Comentarios.
- Notificaciones.
- Búsqueda dentro de documentos.
- Cuotas por usuario o departamento.
- Carga fragmentada.
- Aplicación móvil.
- Sincronización de escritorio.
- Edición en línea.
- Enlaces públicos externos.

---

# 6. Definition of Done

Una tarea se considera terminada cuando:

- El código está implementado.
- El código ejecuta sin errores.
- Las validaciones están implementadas.
- Las Policies o permisos necesarios están implementados.
- Las pruebas relacionadas pasan.
- La interfaz contempla estados de éxito y error.
- La interfaz contempla estado vacío cuando corresponde.
- Las operaciones críticas generan auditoría.
- No se exponen rutas físicas.
- No se exponen archivos directamente.
- La documentación afectada está actualizada.
- El criterio de aceptación fue comprobado.

---

# 7. Resultado esperado

Al concluir las fases, Nube Municipal deberá permitir que un usuario autenticado:

1. Ingrese mediante el sistema central.
2. Sincronice su usuario, departamento, roles y permisos.
3. Consulte su espacio privado.
4. Cree carpetas y subcarpetas.
5. Suba archivos.
6. Descargue archivos mediante rutas protegidas.
7. Renombre y mueva documentos propios.
8. Elimine y restaure elementos.
9. Consulte archivos colaborativos de su departamento.
10. Consulte archivos públicos internos.
11. Ejecute únicamente acciones permitidas.
12. Genere registros de auditoría de las operaciones críticas.

---

# 8. Criterios generales de aceptación del MVP

El MVP se considerará aceptado cuando:

1. El sistema identifique correctamente al usuario.
2. El usuario quede asociado a su departamento.
3. Los roles y permisos se sincronicen.
4. El usuario pueda crear carpetas privadas.
5. El usuario pueda cargar archivos.
6. Los archivos se almacenen en la ruta física correspondiente.
7. Los metadatos se registren en la base de datos.
8. Los archivos privados estén protegidos.
9. Los archivos colaborativos estén limitados al departamento.
10. Los archivos públicos internos requieran autenticación.
11. Las descargas pasen por un controlador.
12. Las operaciones críticas pasen por Policies.
13. Los archivos no sean accesibles directamente por URL.
14. La interfaz funcione con Blade y Tailwind CSS.
15. Las operaciones críticas queden registradas en auditoría.
16. La aplicación pueda desplegarse siguiendo la documentación.
