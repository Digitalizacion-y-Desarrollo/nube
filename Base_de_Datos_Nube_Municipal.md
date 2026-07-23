# Base de Datos — Nube Municipal

## 1. Objetivo

Este documento define el modelo de datos del MVP de **Nube Municipal**, una plataforma interna para almacenar archivos privados, colaborativos y públicos.

El sistema de accesos externo será la fuente oficial de usuarios, departamentos, roles y permisos. Nube Municipal conservará una copia local de esos datos para facilitar relaciones, consultas, autorización y auditoría.

Las tablas propias de la plataforma serán:

- `folders`
- `files`
- `audit_logs`

Las tablas sincronizadas desde el sistema de accesos serán:

- `departments`
- `users`
- `roles`
- `permissions`
- `user_roles`
- `user_permissions`

---

## 2. Convenciones generales

- Los nombres de tablas y columnas se mantendrán en inglés para seguir las convenciones de Laravel.
- En la interfaz se utilizará la palabra **Departamento**.
- Los identificadores locales de usuarios y departamentos serán `BIGINT UNSIGNED`.
- Los identificadores de carpetas y archivos serán `UUID`.
- Los IDs externos se guardarán en columnas llamadas `external_id`.
- No se almacenarán contraseñas.
- Los tokens del sistema de accesos se guardarán únicamente en la sesión del servidor.
- Las rutas físicas se almacenarán como rutas relativas.
- Carpetas y archivos utilizarán eliminación lógica mediante `deleted_at`.

---

# 3. Tablas

## 3.1. Tabla `departments`

Representa los departamentos sincronizados desde el sistema de accesos.

| Campo | Tipo de dato | Restricciones | Descripción |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, autoincremental | Identificador interno |
| `external_id` | `VARCHAR(100)` | UNIQUE, NOT NULL | Identificador en el sistema de accesos |
| `parent_id` | `BIGINT UNSIGNED` | FK, NULL | Departamento padre local |
| `parent_external_id` | `VARCHAR(100)` | NULL | ID externo del departamento padre |
| `name` | `VARCHAR(150)` | NOT NULL | Nombre del departamento |
| `abbreviation` | `VARCHAR(50)` | NULL | Siglas o abreviatura |
| `active` | `BOOLEAN` | DEFAULT true | Estado del departamento |
| `last_synced_at` | `TIMESTAMP` | NULL | Última sincronización |
| `created_at` | `TIMESTAMP` | NOT NULL | Fecha de creación |
| `updated_at` | `TIMESTAMP` | NOT NULL | Fecha de actualización |

### Relaciones

```text
departments.parent_id -> departments.id
```

### Cardinalidad

```text
departments 1 --- N departments
```

Un departamento puede tener muchos departamentos hijos.

---

## 3.2. Tabla `users`

Representa la copia local de los usuarios del sistema de accesos.

| Campo | Tipo de dato | Restricciones | Descripción |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, autoincremental | Identificador interno |
| `external_id` | `VARCHAR(100)` | UNIQUE, NOT NULL | Identificador en el sistema de accesos |
| `department_id` | `BIGINT UNSIGNED` | FK, NULL | Departamento actual |
| `name` | `VARCHAR(100)` | NOT NULL | Nombre del usuario |
| `last_name` | `VARCHAR(150)` | NULL | Apellidos |
| `email` | `VARCHAR(255)` | UNIQUE, NOT NULL | Correo electrónico |
| `active` | `BOOLEAN` | DEFAULT true | Estado del usuario |
| `last_login_at` | `TIMESTAMP` | NULL | Último inicio de sesión |
| `last_synced_at` | `TIMESTAMP` | NULL | Última sincronización |
| `created_at` | `TIMESTAMP` | NOT NULL | Fecha de creación |
| `updated_at` | `TIMESTAMP` | NOT NULL | Fecha de actualización |

### Relaciones

```text
users.department_id -> departments.id
```

### Cardinalidad

```text
departments 1 --- N users
```

Un departamento puede tener muchos usuarios y cada usuario pertenece a un departamento.

---

## 3.3. Tabla `roles`

Catálogo local de roles sincronizados desde el sistema de accesos.

| Campo | Tipo de dato | Restricciones | Descripción |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, autoincremental | Identificador interno |
| `external_id` | `VARCHAR(100)` | UNIQUE, NULL | Identificador externo |
| `name` | `VARCHAR(100)` | UNIQUE, NOT NULL | Nombre técnico |
| `display_name` | `VARCHAR(150)` | NULL | Nombre visible |
| `created_at` | `TIMESTAMP` | NOT NULL | Fecha de creación |
| `updated_at` | `TIMESTAMP` | NOT NULL | Fecha de actualización |

Ejemplos de roles:

```text
administrador
empleado
supervisor
```

---

## 3.4. Tabla `user_roles`

Tabla pivote entre usuarios y roles.

| Campo | Tipo de dato | Restricciones | Descripción |
|---|---|---|---|
| `user_id` | `BIGINT UNSIGNED` | PK compuesta, FK | Usuario relacionado |
| `role_id` | `BIGINT UNSIGNED` | PK compuesta, FK | Rol relacionado |
| `created_at` | `TIMESTAMP` | NOT NULL | Fecha de asignación |

### Relaciones

```text
user_roles.user_id -> users.id
user_roles.role_id -> roles.id
```

### Cardinalidad

```text
users N --- N roles
```

Un usuario puede tener varios roles y un rol puede asignarse a varios usuarios.

---

## 3.5. Tabla `permissions`

Catálogo local de permisos sincronizados desde el sistema de accesos.

| Campo | Tipo de dato | Restricciones | Descripción |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, autoincremental | Identificador interno |
| `external_id` | `VARCHAR(100)` | UNIQUE, NULL | Identificador externo |
| `name` | `VARCHAR(150)` | UNIQUE, NOT NULL | Clave técnica del permiso |
| `display_name` | `VARCHAR(150)` | NULL | Nombre visible |
| `created_at` | `TIMESTAMP` | NOT NULL | Fecha de creación |
| `updated_at` | `TIMESTAMP` | NOT NULL | Fecha de actualización |

Ejemplos de permisos:

```text
nube.acceder
nube.archivos.subir
nube.archivos.descargar
nube.archivos.eliminar
nube.archivos.publicar
nube.administrar
```

---

## 3.6. Tabla `user_permissions`

Tabla pivote entre usuarios y permisos efectivos.

| Campo | Tipo de dato | Restricciones | Descripción |
|---|---|---|---|
| `user_id` | `BIGINT UNSIGNED` | PK compuesta, FK | Usuario relacionado |
| `permission_id` | `BIGINT UNSIGNED` | PK compuesta, FK | Permiso relacionado |
| `created_at` | `TIMESTAMP` | NOT NULL | Fecha de asignación |

### Relaciones

```text
user_permissions.user_id -> users.id
user_permissions.permission_id -> permissions.id
```

### Cardinalidad

```text
users N --- N permissions
```

---

## 3.7. Tabla `folders`

Representa las carpetas lógicas del sistema.

| Campo | Tipo de dato | Restricciones | Descripción |
|---|---|---|---|
| `id` | `UUID` | PK | Identificador de la carpeta |
| `parent_id` | `UUID` | FK, NULL | Carpeta padre |
| `owner_id` | `BIGINT UNSIGNED` | FK, NOT NULL | Usuario creador |
| `department_id` | `BIGINT UNSIGNED` | FK, NOT NULL | Departamento asociado |
| `name` | `VARCHAR(150)` | NOT NULL | Nombre visible |
| `visibility` | `VARCHAR(20)` | NOT NULL | Nivel de visibilidad |
| `path_cache` | `VARCHAR(500)` | NULL | Ruta lógica precalculada |
| `created_at` | `TIMESTAMP` | NOT NULL | Fecha de creación |
| `updated_at` | `TIMESTAMP` | NOT NULL | Fecha de actualización |
| `deleted_at` | `TIMESTAMP` | NULL | Eliminación lógica |

### Valores permitidos para `visibility`

```text
private
collaborative
public
```

### Relaciones

```text
folders.parent_id -> folders.id
folders.owner_id -> users.id
folders.department_id -> departments.id
```

### Cardinalidades

```text
folders 1 --- N folders
users 1 --- N folders
departments 1 --- N folders
```

Una carpeta puede contener muchas subcarpetas.

---

## 3.8. Tabla `files`

Almacena los metadatos de los archivos. El archivo físico se guarda en el almacenamiento local de Laravel.

| Campo | Tipo de dato | Restricciones | Descripción |
|---|---|---|---|
| `id` | `UUID` | PK | Identificador del archivo |
| `folder_id` | `UUID` | FK, NULL | Carpeta lógica |
| `owner_id` | `BIGINT UNSIGNED` | FK, NOT NULL | Propietario |
| `department_id` | `BIGINT UNSIGNED` | FK, NOT NULL | Departamento asociado |
| `original_name` | `VARCHAR(255)` | NOT NULL | Nombre original |
| `display_name` | `VARCHAR(255)` | NOT NULL | Nombre mostrado actualmente |
| `stored_name` | `VARCHAR(255)` | UNIQUE, NOT NULL | Nombre físico |
| `disk` | `VARCHAR(50)` | DEFAULT `nube` | Disco de Laravel |
| `path` | `VARCHAR(500)` | NOT NULL | Ruta relativa |
| `extension` | `VARCHAR(20)` | NULL | Extensión |
| `mime_type` | `VARCHAR(150)` | NULL | Tipo MIME |
| `size_bytes` | `BIGINT UNSIGNED` | NOT NULL | Tamaño en bytes |
| `visibility` | `VARCHAR(20)` | NOT NULL | Clasificación |
| `checksum` | `VARCHAR(64)` | NULL | Hash SHA-256 |
| `uploaded_at` | `TIMESTAMP` | NOT NULL | Fecha real de carga |
| `created_at` | `TIMESTAMP` | NOT NULL | Fecha de creación |
| `updated_at` | `TIMESTAMP` | NOT NULL | Fecha de actualización |
| `deleted_at` | `TIMESTAMP` | NULL | Eliminación lógica |

### Relaciones

```text
files.folder_id -> folders.id
files.owner_id -> users.id
files.department_id -> departments.id
```

### Cardinalidades

```text
folders 1 --- N files
users 1 --- N files
departments 1 --- N files
```

`folder_id` puede ser nulo cuando el archivo se encuentre directamente en una raíz lógica.

---

## 3.9. Tabla `audit_logs`

Registra las acciones importantes realizadas dentro de la plataforma.

| Campo | Tipo de dato | Restricciones | Descripción |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, autoincremental | Identificador |
| `user_id` | `BIGINT UNSIGNED` | FK, NULL | Usuario responsable |
| `action` | `VARCHAR(100)` | NOT NULL | Acción realizada |
| `resource_type` | `VARCHAR(100)` | NULL | Tipo de recurso |
| `resource_id` | `VARCHAR(100)` | NULL | ID del recurso |
| `ip_address` | `VARCHAR(45)` | NULL | IPv4 o IPv6 |
| `user_agent` | `TEXT` | NULL | Navegador o cliente |
| `details` | `JSON` | NULL | Información adicional |
| `created_at` | `TIMESTAMP` | NOT NULL | Fecha del evento |

No se recomienda incluir `updated_at`, porque los registros de auditoría no deberían modificarse.

### Relación

```text
audit_logs.user_id -> users.id
```

### Cardinalidad

```text
users 1 --- N audit_logs
```

Ejemplos de acciones:

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

---

# 4. Resumen de relaciones

```text
departments 1 --- N departments
departments 1 --- N users
departments 1 --- N folders
departments 1 --- N files

users N --- N roles
users N --- N permissions

users 1 --- N folders
users 1 --- N files
users 1 --- N audit_logs

folders 1 --- N folders
folders 1 --- N files
```

---

# 5. Diagrama relacional simplificado

```text
departments
    |
    |----< departments
    |----< users
    |        |----< user_roles >---- roles
    |        |----< user_permissions >---- permissions
    |        |----< folders
    |        |----< files
    |        `----< audit_logs
    |
    |----< folders
    |        |----< folders
    |        `----< files
    |
    `----< files
```

---

# 6. Reglas recomendadas para llaves foráneas

| Relación | Acción al eliminar |
|---|---|
| Departamento padre → departamento hijo | `SET NULL` |
| Departamento → usuario | `RESTRICT` |
| Departamento → carpeta | `RESTRICT` |
| Departamento → archivo | `RESTRICT` |
| Usuario → roles | `CASCADE` en tabla pivote |
| Usuario → permisos | `CASCADE` en tabla pivote |
| Usuario → carpeta | `RESTRICT` |
| Usuario → archivo | `RESTRICT` |
| Carpeta padre → subcarpeta | `RESTRICT` |
| Carpeta → archivo | `RESTRICT` |
| Usuario → auditoría | `SET NULL` |

---

# 7. Índices recomendados

## `departments`

```text
UNIQUE external_id
INDEX parent_id
INDEX active
```

## `users`

```text
UNIQUE external_id
UNIQUE email
INDEX department_id
INDEX active
```

## `roles`

```text
UNIQUE external_id
UNIQUE name
```

## `permissions`

```text
UNIQUE external_id
UNIQUE name
```

## `folders`

```text
INDEX parent_id
INDEX owner_id
INDEX department_id
INDEX visibility
INDEX deleted_at
UNIQUE parent_id, owner_id, name, deleted_at
```

## `files`

```text
UNIQUE stored_name
INDEX folder_id
INDEX owner_id
INDEX department_id
INDEX visibility
INDEX uploaded_at
INDEX deleted_at
```

## `audit_logs`

```text
INDEX user_id
INDEX action
INDEX resource_type, resource_id
INDEX created_at
```

---

# 8. Sincronización con el sistema de accesos

Cada vez que un usuario inicie sesión correctamente, Laravel deberá:

1. Crear o actualizar su departamento.
2. Crear o actualizar su registro local de usuario.
3. Crear o actualizar los roles recibidos.
4. Sincronizar la tabla `user_roles`.
5. Crear o actualizar los permisos recibidos.
6. Sincronizar la tabla `user_permissions`.
7. Actualizar `last_login_at`.
8. Actualizar `last_synced_at`.

No se actualizarán todos los usuarios en cada inicio de sesión. Solo se sincronizarán los datos relacionados con el usuario autenticado.

```text
Sistema de accesos = fuente oficial
Base de datos de Nube Municipal = copia local de trabajo
```

---

# 9. Estructura física relacionada

Los archivos podrán almacenarse con una estructura similar a la siguiente:

```text
storage/app/nube/
└── departamentos/
    └── {department_id}/
        ├── usuarios/
        │   └── {user_id}/
        │       └── privados/
        ├── colaborativos/
        └── publicos/
```

Ejemplo:

```text
storage/app/nube/departamentos/8/usuarios/125/privados/
```

La base de datos almacenará únicamente la ruta relativa y los metadatos necesarios.

---

# 10. Tablas finales del MVP

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
