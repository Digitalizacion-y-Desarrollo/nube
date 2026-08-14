# QA administrativo — casos ejecutados y evidencias

Consolidación del Épico 20 para la sección administrativa (`/admin`) y su efecto
sobre las funciones existentes.

- Fecha de la verificación: **13 de agosto de 2026**.
- Alcance: Épicos 11 a 19, más regresión de los Épicos 01 a 09.
- Resultado global de la suite: **176 pruebas aprobadas, 1198 aserciones**.

## 1. Cómo reproducir la verificación

```powershell
php artisan test
vendor\bin\pint --test
npm run build
php artisan route:list --except-vendor
php artisan schedule:list
git diff --check
```

## 2. Casos automatizados por tarea del Épico 20

### 2.1. Acceso con superusuario, usuario normal y sin sesión

| Caso | Evidencia | Resultado |
|---|---|---|
| Invitado en cualquier sección administrativa | `SuperuserAdministrationTest::test_guest_is_redirected_to_login_from_the_administration_panel` | Redirección a `/login` |
| Usuario normal y permiso administrativo heredado sin rol | `SuperuserAdministrationTest::test_regular_user_and_legacy_administration_permission_are_forbidden` | `403` |
| Superusuario en las siete secciones | `SuperuserAdministrationTest::test_superuser_can_open_every_administrative_section_and_return_to_personal_cloud` | `200` y retorno a la nube personal |
| Invitado, usuario normal y superusuario sin permiso en las 7 rutas de escritura | `AdminSecurityPoliciesTest::test_administrative_write_routes_require_both_the_role_and_the_permission` | Redirección / `403` / `403` |
| Permiso retirado en sesión con copia local intacta | `AdminSecurityPoliciesTest::test_losing_the_permission_in_the_session_closes_administrative_writes` | `403` |
| Navegación personal no expone el panel a no superusuarios | `SuperuserAdministrationTest::test_personal_navigation_only_exposes_administration_to_superusers` | Enlace ausente |

### 2.2. Archivos entre departamentos

| Caso | Evidencia | Resultado |
|---|---|---|
| Cambio de departamento conserva lo privado y corta lo colaborativo | `OwnershipAfterDepartmentChangeTest::test_creator_keeps_private_resources_but_loses_old_department_resources` | Aislamiento correcto |
| Contenido colaborativo antiguo no se expone al nuevo departamento | `OwnershipAfterDepartmentChangeTest::test_department_change_does_not_expose_old_collaborative_content_to_the_new_department` | Sin fuga |
| `admin_area` limitado a su propia área | `OwnershipAfterDepartmentChangeTest::test_area_admin_can_manage_collaborative_resources_from_their_department` | Alcance respetado |
| Movimiento conserva la frontera departamental de almacenamiento | `OwnershipAfterDepartmentChangeTest::test_moving_an_old_file_keeps_its_original_department_storage_boundary` | Ruta correcta |
| Inventario global filtrado por departamento y propietario | `AdminFileExplorerTest::test_explorer_filters_the_global_inventory_and_preserves_safe_metadata` | Filtros correctos |
| Detalle de departamento sin datos sensibles | `AdminDepartmentAdministrationTest::test_department_detail_shows_related_users_shared_files_and_activity_without_sensitive_data` | Sin rutas físicas |

### 2.3. Descarga, visibilidad, papelera, restauración y eliminación definitiva

| Caso | Evidencia | Resultado |
|---|---|---|
| Descarga, reclasificación y envío a papelera autorizados | `AdminFileExplorerTest::test_authorized_superuser_downloads_reclassifies_and_trashes_with_audit` | Operaciones y auditoría correctas |
| Selección colaborativa por departamento o personas | `AdminFileExplorerTest::test_authorized_superuser_selects_the_department_or_specific_people_when_collaborating` | Colaboradores persistidos |
| Restauración a la carpeta original | `AdminGeneralTrashTest::test_authorized_superuser_restores_a_file_to_its_original_folder_with_audit` | Registro y archivo físico coherentes |
| Restauración a la raíz cuando la carpeta fue eliminada | `AdminGeneralTrashTest::test_file_returns_to_the_root_when_its_original_folder_is_also_deleted` | `folder_id` nulo y ruta correcta |
| Carpeta restaurada a la raíz sin su superior | `AdminGeneralTrashTest::test_folder_is_restored_to_the_root_when_its_parent_is_unavailable` | `path_cache` recalculado |
| Eliminación definitiva con nombre exacto y borrado físico | `AdminGeneralTrashTest::test_permanent_deletion_requires_the_exact_name_and_removes_the_physical_file` | Nombre incorrecto rechazado |
| Purga de carpeta bloqueada con contenido retenido | `AdminGeneralTrashTest::test_folder_purge_is_blocked_while_it_still_retains_content` | Bloqueo y desbloqueo correctos |
| Archivo activo no purgable; archivo en papelera no descargable | `AdminSecurityPoliciesTest::test_active_files_cannot_be_purged_and_trashed_files_cannot_be_downloaded` | `404` en ambos |

### 2.4. Auditoría, fallo del API y ausencia del archivo físico

| Caso | Evidencia | Resultado |
|---|---|---|
| Toda mutación administrativa deja evento con actor e IP | `AdminSecurityPoliciesTest::test_every_administrative_mutation_leaves_an_audit_trail` | 6 eventos verificados |
| Bitácora inmutable y reservada al rol | `AdminAuditTrailTest::test_audit_trail_is_reserved_for_superusers_and_stays_read_only` | `405` en POST, PATCH y DELETE |
| Detalle del evento sin rutas ni secretos | `AdminAuditTrailTest::test_event_detail_shows_context_without_physical_paths_or_secrets` | Redacción `[OCULTO]` |
| Distinción entre acciones administrativas y de usuario | `AdminAuditTrailTest::test_audit_trail_separates_administrative_actions_from_user_actions` | Filtro por origen correcto |
| Evento sobrevive a la desaparición del recurso | `AdminStabilizationTest::test_audit_trail_survives_the_deletion_of_the_resource_it_describes` | Detalle consultable |
| API caído durante la revalidación en secciones administrativas | `AdminStabilizationTest::test_administrative_sections_end_the_session_when_the_access_api_is_unavailable` | Cierre de sesión, sin `500` |
| API inalcanzable en una ruta de escritura | `AdminStabilizationTest::test_administrative_write_routes_also_close_when_the_api_is_unreachable` | Sin mutación ni evento administrativo |
| Panel de configuración con API disponible y con error | `AdminSystemSettingsTest::test_live_check_reports_a_successful_connection_and_is_audited`, `::test_live_check_reports_an_unavailable_api_without_leaking_details` | Estados diferenciados |
| El panel no llama al API al cargarse | `AdminSystemSettingsTest::test_settings_do_not_call_the_access_api_when_the_panel_loads` | Cero peticiones |
| Descarga sin copia física | `AdminStabilizationTest::test_administrative_download_fails_safely_when_the_physical_file_is_missing` | `404` sin auditar descarga |
| Reclasificación sin copia física | `AdminStabilizationTest::test_administrative_reclassification_reports_a_neutral_error_when_the_physical_file_is_missing` | Error neutro, registro intacto |
| Envío a papelera sin copia física | `AdminStabilizationTest::test_sending_to_trash_reports_a_neutral_error_when_the_physical_file_is_missing` | Error neutro, registro intacto |
| Restauración sin copia física | `AdminStabilizationTest::test_restoring_a_file_without_its_physical_copy_leaves_the_record_untouched` | Permanece en papelera |
| Purga con copia física ya ausente | `AdminStabilizationTest::test_permanent_deletion_completes_when_the_physical_copy_is_already_gone` | Registro huérfano limpiado |

### 2.5. Validación de rutas y permisos

| Caso | Evidencia | Resultado |
|---|---|---|
| Matriz completa de rutas, middleware, Policies y auditoría | `SEGURIDAD_Y_AUDITORIA.md` | Actualizada |
| Inventario de rutas | `php artisan route:list --except-vendor` | 19 rutas administrativas |
| Middleware por ruta de escritura | `php artisan route:list -v` | `access.session` → `superuser` → `admin.permission` en las 7 |
| Traversal e identificadores desconocidos | `AdminSecurityPoliciesTest::test_administrative_routes_reject_traversal_and_unknown_identifiers` | `404` |
| Almacenamiento privado no servido desde `public` | `SecurityHardeningTest::test_private_storage_has_no_public_link_and_is_not_served_from_public` | `404` en `/storage/...` |
| Cabeceras de seguridad y nonce | `SecurityHardeningTest::test_responses_include_security_headers_and_a_nonce_for_inline_scripts` | Presentes |
| Campos protegidos frente a asignación masiva | `SecurityHardeningTest::test_unvalidated_fields_cannot_overwrite_protected_file_metadata` | Sin sobrescritura |
| Mensajes de error sin rutas físicas | `AdminSecurityPoliciesTest::test_failed_administrative_operations_do_not_expose_physical_paths` | Mensajes neutros |

### 2.6. Documentación

| Entregable | Estado |
|---|---|
| `MANUAL_SUPERUSUARIO.md` | Creado |
| `SEGURIDAD_Y_AUDITORIA.md` | Actualizado con las rutas de los Épicos 15 a 19 |
| `Base_de_Datos_Nube_Municipal.md` | Actualizado con `deleted_by` e índices |
| `ESTADO_DESARROLLO.md` | Bitácora al día |

## 3. Regresión sobre las funciones existentes

La suite completa cubre además autenticación, explorador, carpetas, archivos
privados, colaboración granular, búsqueda global, papelera del usuario final y
purga programada. Todas las clases siguen en verde tras los Épicos 15 a 19:

```text
176 aprobadas · 1198 aserciones
```

Comprobaciones complementarias en verde: Laravel Pint, compilación de vistas
Blade, compilación de Vite, listado de rutas, `schedule:list` y
`git diff --check`.

## 4. Pendiente: QA manual con navegador

Los siguientes casos **no** están cubiertos y requieren una sesión con navegador
conectado. Afectan a los Épicos 06, 07 y 11 a 19.

| Caso | Qué validar |
|---|---|
| Escritorio, tableta y móvil | Maquetación responsive de las siete secciones administrativas |
| Modo claro y oscuro | Contraste y legibilidad, en especial etiquetas de estado |
| Consola del navegador | Ausencia de errores y de recursos `404` |
| Modales | Foco inicial, ciclo de tabulación, cierre con Escape y devolución del foco |
| Confirmación por nombre | Comportamiento del campo y del botón en el navegador |
| Cargas de 200 MB | Comportamiento real del formulario y de los límites de PHP y Apache |
| API real | Pruebas con usuarios activos, inactivos y tokens vencidos contra Accesos |
| Capturas | Evidencia visual final para el cierre del MVP |

## 5. Riesgos aceptados

- `style-src` conserva `unsafe-inline` por los estilos dinámicos actuales; los
  scripts usan nonce y no admiten `unsafe-inline`.
- La purga automática diaria no alcanza carpetas eliminadas.
- Los valores de `last_login_at` anteriores al 13 de agosto de 2026 reflejan la
  última revalidación de sesión, no el último acceso real. No son reconstruibles.
