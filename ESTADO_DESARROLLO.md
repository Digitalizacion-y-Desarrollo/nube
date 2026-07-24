# Estado de desarrollo — Nube Empresarial

Este archivo es la bitácora de continuidad del proyecto. Debe actualizarse al
final de cada sesión de trabajo y consultarse antes de iniciar una nueva.

## Última actualización

- Fecha: 24 de julio de 2026.
- Estado general: Épicos 01, 02 y 03 implementados y enviados a Revisión y QA.
- Próximo trabajo: Épico 04 — Explorador y carpetas privadas.
- Rama o commit: los cambios actuales permanecen en el árbol de trabajo local;
  no se creó ningún commit durante esta sesión.

## Tablero de Trello

- Épico 01 — Preparación técnica y estructura: **Revisión y QA**.
- [Épico 02 — Base de datos y modelos Eloquent](https://trello.com/c/66AfrJsG/2-epic-02-base-de-datos-y-modelos-eloquent):
  **Revisión y QA**.
- [Épico 03 — Integración con sistema de accesos](https://trello.com/c/lxIo64F4/3-epic-03-integraci%C3%B3n-con-sistema-de-accesos):
  **Revisión y QA**.
- [Épico 04 — Explorador y carpetas privadas](https://trello.com/c/qSz1mLPp/4-epic-04-explorador-y-carpetas-privadas):
  **Backlog; siguiente épico**.

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
```

La clave real debe permanecer únicamente en el `.env` local o en el
administrador de secretos del entorno.

## Verificación al cierre

- Pruebas: **41 aprobadas, 148 aserciones**.
- Laravel Pint: aprobado.
- Compilación de Vite: aprobada.
- Rutas: aprobadas.
- Compilación de vistas Blade: aprobada.
- `git diff --check`: aprobado.
- `.env.example`: verificado sin clave secreta.
- Limitación: no había un navegador conectado para efectuar la inspección
  visual interactiva final del login. La estructura, medidas responsive y
  activos se comprobaron contra Figma y mediante pruebas de renderizado Blade.

Comandos de comprobación:

```powershell
php artisan test
vendor\bin\pint --test
npm run build
php artisan route:list --except-vendor
```

## Punto exacto para retomar

Comenzar con el **Épico 04 — Explorador y carpetas privadas**:

1. Volver a consultar la tarjeta de Trello y sus criterios vigentes.
2. Revisar los mockups de Figma correspondientes al explorador.
3. Implementar las secciones Mis archivos, Mi departamento, Públicos y
   Papelera según permisos.
4. Crear navegación jerárquica, listado y breadcrumbs.
5. Implementar creación, renombrado y eliminación lógica de carpetas privadas.
6. Añadir `FolderController`, Form Requests, `FolderPolicy` y auditoría.
7. Probar que un usuario no pueda consultar ni modificar carpetas privadas
   ajenas.

Antes de comenzar, revisar también `AGENT.md`,
`Plan_de_Desarrollo_por_Fases_Nube_Municipal.md`,
`Base_de_Datos_Nube_Municipal.md` y `Propuesta_MVP_Nube_Municipal.md`.
