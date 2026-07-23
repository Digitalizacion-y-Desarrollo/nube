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

Crear seeders para permisos iniciales:

```text
nube.acceder
nube.archivos.subir
nube.archivos.descargar
nube.archivos.eliminar
nube.archivos.publicar
nube.administrar
```

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
- Seeders.
- Catálogo de permisos.

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
5. Crear o actualizar permisos.
6. Sincronizar `user_permissions`.
7. Actualizar `last_login_at`.
8. Actualizar `last_synced_at`.

#### Sesión

- Implementar middleware para validar sesión local.
- Consumir `GET /api/auth/me` cuando sea necesario.
- Detectar token vencido.
- Limpiar la sesión cuando el token no sea válido.
- Verificar el permiso `nube.acceder`.

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

Un usuario válido debe iniciar sesión, quedar registrado o actualizado localmente, conservar su departamento, roles y permisos, y acceder a la aplicación.

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
50 MB por archivo
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

- Los usuarios del mismo departamento pueden visualizarlos.
- Los usuarios del mismo departamento pueden descargarlos.
- Solo el propietario puede renombrarlos.
- Solo el propietario puede eliminarlos.
- Usuarios de otros departamentos no pueden acceder.

### Reglas de archivos públicos internos

- Cualquier usuario autenticado puede visualizarlos.
- Cualquier usuario autenticado puede descargarlos.
- Solo el propietario o un administrador puede modificarlos.
- No deben ser accesibles sin autenticación.

### Actividades

- Implementar selector de clasificación durante la carga.
- Implementar cambio de visibilidad.
- Verificar permiso `nube.archivos.publicar`.
- Crear filtros por visibilidad.
- Limitar consultas colaborativas al departamento actual.
- Separar rutas físicas por visibilidad.
- Actualizar Policies.
- Registrar cambios de visibilidad.

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

- Compartición individual.
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
