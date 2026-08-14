# Manual del superusuario — Nube Municipal

Guía operativa del panel administrativo disponible en `/admin`.

Este documento describe **qué puede hacer** un superusuario, **qué no puede
hacer** y **qué esperar** en cada situación. Para la matriz técnica de rutas,
middleware, Policies y auditoría, consulta `SEGURIDAD_Y_AUDITORIA.md`.

## 1. Quién puede entrar

El acceso al panel exige tres condiciones simultáneas:

| Condición | Origen | Si falta |
|---|---|---|
| Sesión activa | Login contra el sistema de Accesos | Redirección a `/login` |
| Permiso `nube_inicio_ver` | Accesos | Cierre de sesión con aviso |
| Rol exacto `superuser` | Accesos | `403` |

Para **modificar** algo (descargar, reclasificar, enviar a papelera, restaurar o
eliminar definitivamente) se exige además el permiso funcional
`nube_administracion_administrar`. Sin él el panel se ve completo, pero los
botones de acción desaparecen y las rutas responden `403`.

> El rol `superuser` **no** concede permisos sobre archivos o carpetas por sí
> solo. Habilita el panel; cada operación sigue validándose por permiso y
> Policy.

Si Accesos deja de devolver el permiso administrativo, la escritura se cierra en
la siguiente revalidación de sesión (por omisión, a los 300 segundos), aunque la
copia local del usuario todavía lo conserve.

## 2. Qué no hace este panel

La plataforma **no es la fuente de identidad**. El sistema de Accesos es el
único lugar donde se crean o modifican:

- Usuarios, su nombre, correo, estado y departamento.
- Roles.
- Permisos.
- Departamentos.

Las secciones de Usuarios y Departamentos son de **consulta exclusiva**: no
existen rutas locales de creación, edición ni borrado. Si necesitas cambiar
alguno de esos datos, hazlo en Accesos y espera la siguiente sincronización.

La sección de Configuración también es de solo lectura. Los valores se cambian
por despliegue, no desde la aplicación.

## 3. Las siete secciones

### 3.1. Resumen

Vista general con totales de archivos, carpetas, usuarios y departamentos,
espacio utilizado separado entre activo y papelera, distribución por
clasificación, actividad reciente y los cinco departamentos y usuarios con mayor
consumo.

### 3.2. Archivos

Inventario global de todos los departamentos.

- **Filtros**: nombre, departamento, propietario, clasificación, extensión,
  rango de carga y estado (activo, en papelera o ambos).
- **Metadatos**: UUID, nombres, MIME, tamaño, clasificación, propietario,
  departamento, carpeta lógica, colaboración y fechas.
- **Descargar**: siempre a través del controlador. No existen URL públicas.
- **Reclasificar**: privado, colaborativo o público. Al elegir colaborativo
  puedes abarcar todo el departamento propietario o seleccionar personas activas
  concretas de esa área, con permisos internos por persona.
- **Enviar a papelera**: con confirmación en modal.

La ruta física, el nombre almacenado y el checksum **nunca** se muestran.

### 3.3. Departamentos

Listado con búsqueda por nombre o abreviatura y filtro por estado. Cada área
muestra usuarios activos y totales, archivos, carpetas, elementos en papelera,
almacenamiento y fecha de última sincronización.

El detalle añade identidad externa, jerarquía, usuarios relacionados, archivos
colaborativos y públicos activos, y actividad reciente.

### 3.4. Usuarios

Listado con búsqueda por nombre, apellido, correo o identificador externo, y
filtros por departamento, rol y estado.

El detalle muestra identidad, identificador externo, departamento, estado,
último inicio de sesión, última sincronización, roles informativos, permisos
efectivos, consumo, archivos (incluidos los que están en papelera) y actividad
auditada.

> **Sobre «último inicio de sesión»**: hasta el 13 de agosto de 2026 este campo
> se reescribía en cada revalidación de sesión, así que los valores anteriores a
> esa fecha reflejan la última validación, no el último acceso real. A partir de
> la corrección sólo lo actualiza un inicio de sesión genuino.

### 3.5. Papelera

Administración centralizada de lo eliminado, en dos secciones.

**Archivos eliminados**

- Filtros por nombre, persona (propietario o quien eliminó), departamento y
  rango de fechas.
- Muestra quién eliminó cada elemento y la fecha de purga prevista.
- **Restaurar**: vuelve a su carpeta original si sigue activa; si la carpeta
  también fue eliminada, el archivo regresa a la raíz de su clasificación.
- **Eliminar definitivamente**: borra el registro y la copia física.

**Carpetas eliminadas**

- **Restaurar**: si su carpeta superior ya no existe, vuelve a la raíz y se
  recalculan las rutas lógicas de sus descendientes.
- **Eliminar definitivamente**: sólo es posible cuando la carpeta ya no retiene
  archivos ni subcarpetas. El botón aparece deshabilitado en caso contrario.

> **Confirmación reforzada**: toda eliminación definitiva exige escribir el
> **nombre exacto** del elemento. La comprobación se valida en el servidor, así
> que no basta con manipular el navegador.

### 3.6. Auditoría

Bitácora inmutable de la plataforma.

- **Filtros**: texto en acción o identificador, usuario, departamento del actor,
  acción exacta, tipo de recurso, dirección IP, origen y rango de fechas.
- **Origen**: distingue acciones administrativas (clave con prefijo `admin.`) de
  las de usuarios normales.
- **Detalle**: actor, correo, departamento, IP, agente de usuario, recurso,
  contenido del campo `details` y los diez eventos más recientes del mismo
  recurso.

Los registros no pueden editarse ni eliminarse desde la plataforma. En el
detalle, las rutas físicas, nombres almacenados, checksums y cualquier valor que
parezca un secreto se muestran como `[OCULTO]`.

### 3.7. Configuración

Estado técnico del entorno: límite de carga y su alineación real con PHP,
extensiones permitidas, disco y ruta raíz relativa al proyecto, espacio
utilizado y disponible, retención y purga de papelera, y estado del API de
Accesos.

El estado del API se presenta en dos formas deliberadamente distintas:

- **Última validación de tu sesión**: evidencia de que el API respondió, sin
  coste alguno.
- **Comprobar conexión ahora**: consulta en vivo bajo demanda. El panel no la
  ejecuta al cargarse para no generar tráfico externo en cada visita.

El panel nunca muestra tokens, la clave del sistema, contraseñas ni las rutas
físicas de los archivos.

## 3.8. Tu foto de perfil

El avatar que acompaña a tu nombre y departamento, tanto en el panel
administrativo como en tu nube personal, es un enlace a `/perfil`. Ahí puedes
subir una foto JPG o PNG, o volver a la predeterminada.

Si no has subido ninguna, la foto predeterminada son **tus iniciales** sobre el
color institucional, generadas por la propia aplicación.

Al seleccionar un archivo verás una **vista previa** antes de guardar, con el
nombre y el peso de la imagen, y podrás descartar la selección. El límite es de
**10 MB** por imagen.

La imagen se guarda en el almacenamiento privado, se sirve por controlador y
sólo es visible desde tu propia sesión. Es un dato local: el sistema de Accesos
no la gestiona y una sincronización no la borra.

## 4. Qué queda registrado

Toda operación administrativa genera un evento con actor, dirección IP, agente
de usuario y metadatos no sensibles:

```text
admin.file.metadata_viewed
admin.file.downloaded
admin.file.visibility_changed
admin.file.sharing_configured
admin.file.trashed
admin.trash.file_restored
admin.trash.file_purged
admin.trash.folder_restored
admin.trash.folder_purged
admin.settings.connection_checked
profile.avatar_updated
profile.avatar_removed
```

## 5. Situaciones frecuentes

| Situación | Qué verás | Qué significa |
|---|---|---|
| Entras sin el rol `superuser` | `403` | El rol se asigna en Accesos |
| Tienes el rol pero no ves botones de acción | Aviso en la sección | Falta `nube_administracion_administrar` |
| Abres un archivo en papelera desde Archivos | `404` | Las rutas de descarga y reclasificación sólo operan sobre activos |
| Identificador manipulado en la URL | `404` | La ruta falla cerrada antes de evaluar permisos |
| «No fue posible…» sin más detalle | Mensaje neutro | Es intencional: los errores no revelan rutas ni datos internos |
| La sesión se cierra de golpe | Redirección a `/login` | El API de Accesos no respondió en la revalidación |
| No puedes purgar una carpeta | Botón deshabilitado | Todavía retiene archivos o subcarpetas |
| Restaurar un archivo falla | Error en la papelera | Su copia física ya no está en el disco |

## 6. Límites conocidos

- **La purga automática diaria sólo alcanza archivos.** Las carpetas eliminadas
  permanecen en la papelera hasta que un superusuario las elimina a mano.
- **Un archivo sin copia física no puede restaurarse.** Sí puede eliminarse
  definitivamente, lo que limpia el registro huérfano.
- **El filtro por departamento de la auditoría usa el área del actor**, porque
  los eventos no almacenan un departamento propio.
- **La sección administrativa no permite mutaciones avanzadas** (mover, renombrar
  o crear contenido). Eso está fuera del alcance del MVP.

## 7. Documentos relacionados

- `SEGURIDAD_Y_AUDITORIA.md` — matriz de rutas, autorización y auditoría.
- `QA_ADMINISTRATIVO.md` — casos verificados y evidencias.
- `../AGENT.md` — reglas del proyecto.
- `../ESTADO_DESARROLLO.md` — bitácora de continuidad.
