# Propuesta de Desarrollo
## Plataforma interna de almacenamiento de archivos — MVP

**Fecha de inicio:** jueves 23 de julio de 2026  
**Fecha de entrega:** viernes 31 de julio de 2026  
**Duración disponible:** 7 días hábiles  
**Nombre provisional:** Nube Municipal

---

## 1. Objetivo del proyecto

Desarrollar una plataforma web interna similar a OneDrive que permita a los colaboradores de la empresa almacenar, consultar, descargar y administrar documentos.

Cada usuario estará relacionado con un área o departamento de la empresa. Los archivos se almacenarán físicamente dentro del servidor del proyecto, organizados mediante carpetas por área y usuario.

La autenticación y la identificación del área del usuario serán proporcionadas por el sistema de accesos existente. El proyecto no incluirá la construcción de un sistema de autenticación independiente.

---

## 2. Tecnologías autorizadas

El MVP se desarrollará con:

- Laravel 12.
- Blade.
- Tailwind CSS.
- JavaScript nativo únicamente para interacciones básicas.
- MySQL o PostgreSQL.
- Laravel Storage con almacenamiento local.

No se utilizarán:

- React.
- Vue.
- Livewire.
- Alpine.js.
- Servicios externos de almacenamiento.
- Arquitectura de microservicios.

---

## 3. Integración con el sistema de accesos

La plataforma consumirá el API documentado en:

`https://accesos.digitalneza.com/api-docs`

El sistema de accesos será responsable de:

- Validar las credenciales del usuario.
- Entregar la identidad del usuario.
- Entregar su departamento padre e hijo.
- Entregar roles y permisos.
- Validar la sesión mediante token.
- Cerrar la sesión central.
- Gestionar la recuperación de contraseña.

### 3.1 Endpoints contemplados

#### Inicio de sesión

```http
POST /api/auth/login
```

Laravel enviará:

```json
{
  "email": "usuario@correo.com",
  "password": "contraseña",
  "system_key": "valor_configurado_en_env"
}
```

La respuesta deberá contener, como mínimo:

- Token Bearer.
- ID externo del usuario.
- Nombre y apellidos.
- Correo electrónico.
- Departamento padre.
- Departamento hijo.
- Roles.
- Permisos.

#### Validación de sesión

```http
GET /api/auth/me
```

Se utilizará para:

- Confirmar que el token sigue vigente.
- Actualizar información del usuario.
- Actualizar departamento, roles y permisos.

#### Cierre de sesión

```http
POST /api/auth/logout
```

Laravel deberá revocar la sesión central y después destruir la sesión local.

#### Recuperación de contraseña

```http
POST /api/auth/forgot-password
```

La recuperación será administrada por el sistema central.

#### Consulta de departamentos

```http
GET /api/departamentos
```

Permitirá sincronizar el catálogo de áreas o departamentos.

#### Consulta de usuarios asignados al sistema

```http
GET /api/integrations/users
```

Podrá utilizarse para sincronización, administración y funciones futuras de compartición.

### 3.2 Configuración en Laravel

La URL y la clave del sistema deberán almacenarse en `.env`:

```env
ACCESS_API_URL=https://accesos.digitalneza.com
ACCESS_SYSTEM_KEY=clave_del_sistema
ACCESS_TIMEOUT=10
```

La clave nunca deberá escribirse directamente en el código fuente ni subirse al repositorio.

### 3.3 Sesión local

Después de un login exitoso:

1. Laravel recibe el token.
2. Guarda el token en la sesión del servidor.
3. Crea o actualiza el usuario local.
4. Crea o actualiza el área local.
5. Guarda roles y permisos relevantes.
6. Inicia la sesión de Laravel.
7. Redirige al explorador de archivos.

No se almacenarán contraseñas en la base de datos de la nube.

---

## 4. Alcance funcional del MVP

### 4.1 Página principal

El usuario visualizará:

- Mis archivos privados.
- Archivos colaborativos de mi área.
- Archivos públicos internos.
- Formulario para subir documentos.
- Indicadores básicos de cantidad y espacio utilizado.

### 4.2 Gestión de carpetas

El usuario podrá:

- Crear carpetas dentro de su espacio privado.
- Abrir carpetas.
- Crear subcarpetas.
- Renombrar carpetas propias.
- Eliminar carpetas vacías.
- Visualizar la ruta actual mediante migas de navegación.

Para el MVP, las carpetas colaborativas y públicas podrán limitarse a una carpeta principal por área.

### 4.3 Gestión de archivos

El usuario podrá:

- Subir archivos.
- Consultar archivos.
- Descargar archivos.
- Renombrar archivos propios.
- Mover archivos entre carpetas privadas.
- Eliminar archivos.
- Consultar información básica del documento.

La información mostrada incluirá:

- Nombre original.
- Tipo de archivo.
- Tamaño.
- Propietario.
- Fecha de carga.
- Clasificación.
- Área correspondiente.

---

## 5. Clasificación de documentos

### 5.1 Privado

Solo podrá ser consultado por su propietario.

Ruta conceptual:

```text
storage/app/nube/areas/{area_id}/usuarios/{user_id}/privados/
```

### 5.2 Colaborativo

Podrá ser consultado por los usuarios pertenecientes a la misma área.

Ruta conceptual:

```text
storage/app/nube/areas/{area_id}/colaborativos/
```

Para el MVP:

- En colaboración para toda el área, los miembros podrán visualizar y
  descargar.
- En colaboración con personas específicas, el propietario podrá asignar
  permisos internos de ver, descargar, renombrar, mover y eliminar por
  colaborador. Estos permisos permanecerán en Nube Municipal y se combinarán
  con los permisos funcionales del sistema de accesos.
- Solo el propietario podrá renombrar o eliminar.

### 5.3 Público interno

Podrá ser consultado por todos los usuarios autenticados de la empresa.

Ruta conceptual:

```text
storage/app/nube/areas/{area_id}/publicos/
```

“Público” significará público dentro de la empresa, no accesible desde Internet.

---

## 6. Estructura de almacenamiento

Los documentos se guardarán dentro de:

```text
storage/app/nube/
```

Estructura propuesta:

```text
storage/app/nube/
├── areas/
│   └── {area_id}/
│       ├── usuarios/
│       │   └── {user_id}/
│       │       └── privados/
│       │           ├── {folder_id}/
│       │           └── archivos
│       ├── colaborativos/
│       └── publicos/
├── papelera/
│   └── {area_id}/
└── temporales/
```

Se utilizarán identificadores numéricos o UUID para áreas, usuarios y archivos.

Ejemplo:

```text
storage/app/nube/areas/8/usuarios/125/privados/
```

Aunque el usuario suba un archivo llamado:

```text
Contrato laboral julio 2026.pdf
```

físicamente podrá almacenarse como:

```text
01982f57-ec74-720f-a86f-4b598c76a124.pdf
```

El nombre original se conservará en la base de datos.

### 6.1 Disco privado de Laravel

```php
'nube' => [
    'driver' => 'local',
    'root' => storage_path('app/nube'),
    'visibility' => 'private',
    'throw' => true,
],
```

Los archivos no se guardarán dentro de `public` y no se expondrán mediante `storage:link`.

Toda descarga deberá pasar por un controlador de Laravel.

---

## 7. Seguridad y autorización

La autorización se implementará mediante Laravel Policies.

### 7.1 Reglas de acceso

#### Archivos privados

```text
archivo.owner_id = usuario.id
```

#### Archivos colaborativos

```text
archivo.area_id = usuario.area_id
```

además de tener clasificación colaborativa.

#### Archivos públicos internos

Podrán ser consultados por cualquier usuario autenticado.

### 7.2 Acciones protegidas

Laravel validará permisos antes de:

- Mostrar información.
- Descargar.
- Renombrar.
- Mover.
- Eliminar.
- Cambiar clasificación.

La ubicación física del archivo nunca será el único mecanismo de seguridad.

### 7.3 Permisos centrales

El sistema de accesos administra el catálogo oficial y devuelve los permisos
efectivos como una lista plana de claves globalmente únicas. Las claves
incluyen el recurso y utilizan guion bajo, por ejemplo:

```text
nube_inicio_ver
nube_mis_archivos_subir
nube_departamento_descargar
nube_publicos_publicar
nube_papelera_restaurar
nube_administracion_administrar
```

Los 28 permisos del MVP están agrupados por recurso en la Fase 3 del plan de
desarrollo. Nube Municipal los sincroniza dinámicamente durante el login y no
mantiene un catálogo fijo en producción. Los permisos efectivos autorizan las
capacidades y las Policies locales deciden si el usuario puede operar sobre un
archivo o carpeta específicos. Los roles son informativos salvo el rol
`superuser`, utilizado únicamente para habilitar el panel administrativo de
consulta; este rol no sustituye permisos funcionales.

---

## 8. Modelo de datos

### 8.1 Tabla `users`

```text
id
external_id
name
apellido_paterno
apellido_materno
email
area_id
role
active
last_synced_at
created_at
updated_at
```

### 8.2 Tabla `areas`

```text
id
external_id
parent_external_id
name
siglas
active
created_at
updated_at
```

### 8.3 Tabla `folders`

```text
id
parent_id
owner_id
area_id
name
visibility
created_at
updated_at
deleted_at
```

### 8.4 Tabla `files`

```text
id
folder_id
owner_id
area_id
original_name
stored_name
disk
path
extension
mime_type
size
visibility
created_at
updated_at
deleted_at
```

### 8.5 Tabla `audit_logs`

```text
id
user_id
action
resource_type
resource_id
ip_address
details
created_at
```

---

## 9. Reglas de carga

El MVP incluirá validaciones de:

- Archivo obligatorio.
- Tamaño máximo configurable.
- Extensiones permitidas.
- Nombre original.
- Clasificación válida.
- Carpeta destino válida.
- Propiedad de la carpeta.
- Área del usuario.

Tipos iniciales sugeridos:

- PDF.
- DOC y DOCX.
- XLS y XLSX.
- PPT y PPTX.
- TXT.
- CSV.
- JPG, JPEG y PNG.
- ZIP.

Tamaño máximo inicial sugerido:

```text
200 MB por archivo
```

El límite deberá coincidir en Laravel, PHP y Nginx o Apache.

---

## 10. Interfaz del MVP

La interfaz incluirá:

- Modos claro y oscuro con preferencia persistente.
- Barra lateral.
- Encabezado con nombre del usuario y área.
- Vista tipo lista.
- Botón de carga.
- Botón para crear carpeta.
- Selector de clasificación.
- Mensajes de éxito y error.
- Diseño adaptable básico.

Secciones principales:

- Mis archivos.
- Mi área.
- Públicos.
- Papelera.

La papelera usa eliminación lógica durante 30 días. Al vencer el plazo, una
tarea diaria elimina de forma permanente el archivo físico y su registro; el
usuario también puede adelantar esta acción mediante confirmación explícita.

---

## 11. Funciones incluidas

- Integración con el API de accesos.
- Creación o actualización local de usuario y área.
- Dashboard principal.
- Exploración de archivos privados.
- Exploración de archivos colaborativos del área.
- Exploración de archivos públicos internos.
- Creación de carpetas privadas.
- Carga de archivos.
- Descarga segura.
- Renombrado.
- Movimiento entre carpetas privadas.
- Eliminación lógica.
- Clasificación privada, colaborativa o pública interna.
- Validaciones de extensión y tamaño.
- Policies de autorización.
- Registro básico de operaciones.
- Diseño con Blade y Tailwind CSS.
- Mensajes de confirmación y error.
- Migraciones y seeders.
- Instrucciones de instalación.
- Panel administrativo de consulta para el rol `superuser`, con resumen,
  archivos, departamentos, usuarios, papelera, auditoría y configuración.
- Dashboard administrativo con totales activos y eliminados, espacio utilizado,
  distribución por visibilidad, actividad y rankings de consumo por usuario y
  departamento.

---

## 12. Funciones excluidas del MVP

Para garantizar la entrega del viernes 31 de julio, no se incluirán:

- Edición simultánea.
- Edición de Word, Excel o PowerPoint desde el navegador.
- Historial de versiones.
- Compartición con usuarios de otros departamentos.
- Compartición entre áreas específicas.
- Enlaces públicos externos.
- Vista previa avanzada.
- Miniaturas.
- Antivirus.
- Búsqueda dentro del contenido.
- Notificaciones.
- Comentarios.
- Solicitudes de acceso.
- Favoritos.
- Aplicación móvil.
- Sincronización de escritorio.
- Carga fragmentada.
- Panel administrativo avanzado con edición de usuarios, departamentos o
  configuración.
- Cuotas individuales o departamentales.

---

## 13. Cronograma de trabajo

### Jueves 23 de julio — Preparación

- Crear el proyecto Laravel 12.
- Configurar base de datos.
- Configurar Tailwind CSS.
- Crear repositorio.
- Configurar disco local `nube`.
- Crear migraciones iniciales.
- Preparar layout principal.

**Entregable:** proyecto ejecutable, base de datos conectada y estructura inicial.

### Viernes 24 de julio — API de accesos

- Crear formulario Blade de login.
- Consumir `POST /api/auth/login`.
- Guardar token en sesión.
- Crear o actualizar usuario local.
- Crear o actualizar departamentos.
- Registrar roles y permisos.
- Implementar middleware.
- Consumir `GET /api/auth/me`.
- Implementar logout.
- Manejar errores `401`, `403`, `404`, `422` y fallos de conexión.

**Entregable:** usuario autenticado y área disponible dentro de Laravel.

### Lunes 27 de julio — Carpetas y archivos privados

- Crear módulo de carpetas.
- Implementar navegación.
- Construir formulario de carga.
- Guardar archivos físicamente.
- Registrar metadatos.
- Mostrar archivos privados.

**Entregable:** creación de carpetas y carga de archivos privados.

### Martes 28 de julio — Operaciones de archivo

- Implementar descarga segura.
- Implementar renombrado.
- Implementar movimiento.
- Implementar eliminación lógica.
- Crear Form Requests.
- Manejar errores de almacenamiento.

**Entregable:** gestión básica completa de documentos privados.

### Miércoles 29 de julio — Colaborativos, públicos y permisos

- Implementar clasificación.
- Implementar archivos colaborativos.
- Implementar archivos públicos internos.
- Crear Policies.
- Proteger rutas y controladores.

**Entregable:** tres niveles de visibilidad funcionando.

### Jueves 30 de julio — Interfaz, auditoría y pruebas

- Finalizar diseño con Tailwind CSS.
- Crear navegación lateral.
- Agregar mensajes de éxito y error.
- Implementar auditoría básica.
- Ejecutar pruebas funcionales.
- Verificar estructura física por área y usuario.

**Entregable:** versión candidata del MVP.

### Viernes 31 de julio — Estabilización y entrega

- Corregir errores.
- Ejecutar pruebas finales.
- Preparar datos demostrativos.
- Revisar permisos.
- Revisar configuración del servidor.
- Preparar documentación.
- Realizar demostración.

**Entregable final:** MVP funcional y desplegable.

---

## 14. Criterios de aceptación

El MVP se considerará aceptado cuando:

1. El sistema identifique al usuario y su área.
2. El usuario pueda crear carpetas privadas.
3. El usuario pueda subir un archivo.
4. El archivo se almacene en la carpeta física correspondiente.
5. Los metadatos se registren en la base de datos.
6. El propietario pueda descargar el archivo.
7. Un usuario no pueda acceder a archivos privados ajenos.
8. Los miembros de un área puedan consultar archivos colaborativos de esa área.
9. Los usuarios no puedan consultar archivos colaborativos de otra área.
10. Los usuarios autenticados puedan consultar archivos públicos internos.
11. El propietario pueda renombrar y eliminar sus archivos.
12. Las descargas pasen por validación de permisos.
13. Los archivos no sean accesibles directamente mediante URL pública.
14. La interfaz funcione con Blade y Tailwind CSS.
15. Las operaciones críticas queden registradas en auditoría.

---

## 15. Riesgos principales

### 15.1 Integración con el API

Debe confirmarse desde el primer día:

- Clave del sistema.
- Formato exacto de respuestas.
- Vigencia del token.
- Manejo de errores.
- Permisos disponibles.

### 15.2 Configuración del servidor

La carga de archivos dependerá de:

- `upload_max_filesize`.
- `post_max_size`.
- Límites de Nginx o Apache.
- Permisos de escritura.

### 15.3 Cambios de alcance

Cualquier función adicional solicitada durante la semana puede comprometer la entrega.

### 15.4 Almacenamiento local

El servidor deberá contar con:

- Espacio suficiente.
- Volumen persistente.
- Permisos de escritura.
- Respaldo automático.
- Monitoreo de espacio disponible.

---

## 16. Supuestos

La propuesta considera que:

- Trabajará una persona durante los siete días hábiles.
- El ambiente ya cuenta con PHP, Composer, Node.js y base de datos.
- El API de accesos estará disponible.
- La clave del sistema será entregada a tiempo.
- No se requerirá edición de documentos en línea.
- No se implementará antivirus en el MVP.
- El despliegue será en un solo servidor.
- El almacenamiento será local o estará montado como volumen persistente.
- Las revisiones se realizarán diariamente.

---

## 17. Entregables finales

El viernes 31 de julio de 2026 se entregará:

- Código fuente.
- Migraciones.
- Seeders o datos de prueba.
- Archivo `.env.example`.
- Manual de instalación.
- Manual básico de usuario.
- Lista de funcionalidades implementadas.
- Lista de pendientes para la siguiente etapa.
- MVP desplegable.

---

## 18. Recomendación final

El alcance mínimo obligatorio para proteger la fecha de entrega será:

- Autenticación mediante el API central.
- Identificación de usuario y área.
- Carpetas privadas.
- Carga y descarga de archivos.
- Eliminación lógica.
- Archivos colaborativos por área.
- Archivos públicos internos.
- Policies de autorización.
- Almacenamiento físico por área y usuario.

Cualquier función adicional deberá tratarse como mejora posterior al MVP.
