{{--
    Sin CTA basado en sesión: el modo mantenimiento intercepta la petición
    antes de que la sesión arranque, así que comprobar auth() aquí podría
    lanzar un error secundario. Ver components/layouts/error.blade.php.
--}}
<x-layouts.error
    code="503"
    title="En mantenimiento"
    message="Estamos realizando tareas de mantenimiento programado. La plataforma volverá a estar disponible en unos minutos."
    :requires-session="false"
/>
