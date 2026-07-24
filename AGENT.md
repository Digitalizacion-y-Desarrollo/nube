# AGENT.md — Nube Municipal

## Propósito

Este repositorio contiene el MVP de **Nube Municipal**, una plataforma web
interna similar a OneDrive para almacenar, organizar, descargar y administrar
archivos privados, colaborativos y públicos internos.

Antes de implementar cambios, consulta como fuentes de verdad:

- `ESTADO_DESARROLLO.md`
- `Propuesta_MVP_Nube_Municipal.md`
- `Plan_de_Desarrollo_por_Fases_Nube_Municipal.md`
- `Base_de_Datos_Nube_Municipal.md`

Si existe una contradicción, prioriza el modelo detallado de
`Base_de_Datos_Nube_Municipal.md` para datos, el plan por fases para el orden
de implementación y la propuesta para el alcance general del MVP.

`ESTADO_DESARROLLO.md` es la bitácora de continuidad: debe actualizarse al
final de cada sesión y define el punto exacto desde el cual retomar.

## Tecnologías obligatorias

- Laravel 12.
- Blade.
- Tailwind CSS.
- JavaScript nativo para interacciones básicas.
- MySQL o PostgreSQL.
- Vite.
- Laravel Storage con un disco local y privado llamado `nube`.

No incorporar React, Vue, Livewire, Alpine.js, microservicios, almacenamiento
externo ni edición de documentos en línea.

## Integración de autenticación

El sistema de accesos externo es la fuente oficial de usuarios, departamentos,
roles y permisos. Nube Municipal mantiene únicamente una copia local de
trabajo.

- API base: `https://accesos.digitalneza.com`.
- Configurar mediante `ACCESS_API_URL`, `ACCESS_SYSTEM_KEY` y
  `ACCESS_TIMEOUT`.
- Nunca escribir claves en el código ni versionar secretos.
- Nunca almacenar contraseñas.
- Guardar el token Bearer únicamente en la sesión del servidor.
- Implementar login, consulta de usuario, logout y recuperación mediante el API.
- Sincronizar en cada login el usuario autenticado, su departamento, roles y
  permisos.
- Verificar el permiso `nube_inicio_ver`.
- Manejar explícitamente respuestas 401, 403, 404, 422, 500, timeouts y API no
  disponible.

El sistema de accesos es la única fuente oficial del catálogo de permisos. El
API devuelve una lista plana de claves globalmente únicas, prefijadas por
recurso y escritas con guion bajo. El catálogo operativo de 28 permisos está
definido en la Fase 3 de `Plan_de_Desarrollo_por_Fases_Nube_Municipal.md`.

- Los roles son informativos y nunca autorizan acciones por sí solos.
- Autorizar exclusivamente con los permisos efectivos del usuario.
- Crear o actualizar localmente cada permiso conforme sea recibido del API.
- Sincronizar exactamente `user_permissions` y retirar de ese usuario los
  permisos que el API deje de devolver.
- No mantener un catálogo fijo en seeders de producción.
- No eliminar globalmente un permiso durante el login, porque puede pertenecer
  a otros usuarios.
- Combinar permisos con Policies para comprobar propietario, departamento,
  visibilidad y estado del recurso.

## Modelo de datos

Las tablas del MVP son:

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

Convenciones:

- Tablas y columnas en inglés.
- `BIGINT UNSIGNED` para IDs locales de usuarios y departamentos.
- UUID para carpetas y archivos.
- IDs del sistema de accesos en `external_id`.
- Rutas físicas almacenadas como rutas relativas.
- `folders` y `files` usan eliminación lógica.
- `audit_logs` es inmutable y no necesita `updated_at`.
- Respetar las relaciones, índices, unicidad y reglas de llaves foráneas
  definidas en el documento de base de datos.

Valores válidos de visibilidad:

```text
private
collaborative
public
```

## Almacenamiento

Guardar los archivos fuera de `public`, bajo `storage/app/nube/`. No usar
`storage:link` para el disco privado.

Estructura conceptual:

```text
storage/app/nube/
├── departamentos/
│   └── {department_id}/
│       ├── usuarios/{user_id}/privados/
│       ├── colaborativos/
│       └── publicos/
├── papelera/
└── temporales/
```

Reglas obligatorias:

- Generar nombres físicos seguros y aleatorios.
- Conservar nombre original y nombre visible en la base de datos.
- No aceptar rutas físicas enviadas por el navegador.
- Evitar path traversal.
- Toda descarga debe pasar por un controlador y una Policy.
- Comprobar la existencia física antes de descargar.
- Mantener consistencia transaccional entre metadatos y archivo físico.
- Eliminar el archivo físico si falla el registro en la base de datos.
- No crear registros si falla el almacenamiento.

Tipos iniciales permitidos: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, CSV,
JPG, JPEG, PNG y ZIP. El límite inicial es de 50 MB y debe coincidir en Laravel,
PHP y Apache o Nginx.

## Autorización

Usar Policies para todas las acciones sensibles y Form Requests para validar
entradas. La ubicación física nunca sustituye la autorización.

- Privado: solo el propietario puede ver, descargar, modificar y eliminar.
- Colaborativo: usuarios del mismo departamento pueden ver y descargar; solo
  el propietario puede renombrar o eliminar.
- Público interno: cualquier usuario autenticado puede ver y descargar; solo
  el propietario o un administrador puede modificar.
- Publicar o cambiar visibilidad requiere el permiso correspondiente.
- Carpetas privadas solo pueden ser modificadas por su propietario.
- Una subcarpeta conserva la visibilidad de su carpeta padre.
- No permitir operaciones sobre carpetas eliminadas.

Aplicar CSRF, validación de UUID, protección contra asignación masiva,
validación de extensión y MIME, sanitización de nombres y mensajes de error que
no expongan detalles técnicos en producción.

## Funcionalidad del MVP

Prioridad crítica:

1. Preparación técnica.
2. Modelo de datos.
3. Integración con el sistema de accesos.
4. Carpetas privadas y navegación jerárquica.
5. Carga y descarga segura de archivos.
6. Renombrado, movimiento, eliminación lógica y restauración.
7. Archivos colaborativos y públicos internos.
8. Policies, seguridad y auditoría.
9. Interfaz Blade/Tailwind alineada con Figma.
10. Pruebas, despliegue y documentación.

Secciones principales:

- Mis archivos.
- Mi departamento.
- Públicos.
- Papelera.

## Interfaz

- Admitir modos claro y oscuro, persistir la preferencia del usuario y evitar
  destellos del tema incorrecto durante la carga.
- Seguir los mockups aprobados en Figma.
- Color principal: `#601633`.
- Color secundario: `#BE985C`.
- Usar componentes Blade reutilizables.
- Implementar barra lateral, encabezado, breadcrumbs, vista tipo lista,
  modales, alertas, estados vacíos, indicadores de carga y menús de acciones.
- Cubrir estados hover, focus, active, disabled, éxito, error y validación.
- Mantener diseño responsive y accesible.
- Incluir filtros, paginación y ordenamiento básico cuando correspondan.

## Auditoría

Registrar al menos:

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

Guardar, según corresponda, usuario, acción, recurso, IP, user agent, detalles
JSON y fecha.

## Pruebas

Crear pruebas Feature para los flujos principales y pruebas Unit para servicios
críticos. Cubrir como mínimo:

- Login, logout, token vencido, falta de permisos y API no disponible.
- Sincronización de usuario, departamento, roles y permisos.
- CRUD permitido de carpetas y bloqueo de carpetas ajenas.
- Carga válida e inválida por extensión, MIME y tamaño.
- Descarga, renombrado, movimiento, eliminación y restauración.
- Acceso privado, colaborativo y público según usuario y departamento.
- Bloqueo de acceso directo o manipulación de URL.
- Fallos de almacenamiento y consistencia entre base de datos y disco.

## Fuera del MVP

No implementar salvo solicitud explícita de ampliación de alcance:

- Compartición individual o entre áreas específicas.
- Historial de versiones.
- Vista previa avanzada o miniaturas.
- Antivirus.
- Comentarios o notificaciones.
- Búsqueda dentro de documentos.
- Cuotas por usuario o departamento.
- Carga fragmentada.
- Aplicación móvil o sincronización de escritorio.
- Edición en línea.
- Enlaces públicos externos.
- Panel administrativo avanzado.

## Definition of Done

Una tarea está terminada únicamente cuando:

- El código ejecuta sin errores.
- Incluye validaciones y autorización.
- Las pruebas relacionadas pasan.
- La interfaz contempla éxito, error y estado vacío.
- Las operaciones críticas generan auditoría.
- No se exponen archivos ni rutas físicas.
- Se verificó el criterio de aceptación correspondiente.
- Se actualizó la documentación afectada.

Mantener el alcance del MVP, preservar la separación entre autenticación,
negocio, almacenamiento y presentación, y no marcar una funcionalidad como
completa sin comprobarla.
