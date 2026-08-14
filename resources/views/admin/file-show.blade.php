<x-layouts.admin title="Metadatos del archivo" :user="$user">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <a href="{{ url()->previous() }}" class="mb-2 inline-flex items-center gap-1 text-xs font-semibold text-brand hover:underline dark:text-gold">← Volver al explorador</a>
            <h2 class="truncate text-xl font-extrabold">{{ $file->display_name }}</h2>
            <p class="mt-1 text-sm text-muted">Metadatos operativos. La ubicación física, el nombre almacenado y el checksum permanecen ocultos.</p>
        </div>
        @if ($file->trashed())
            <span class="inline-flex shrink-0 rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-300">En papelera</span>
        @else
            <span class="inline-flex shrink-0 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Activo</span>
        @endif
    </div>

    <section class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-line bg-surface p-5">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-muted">Identificación</h3>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><dt class="text-xs font-semibold text-muted">Identificador</dt><dd class="mt-1 break-all text-sm font-medium">{{ $file->id }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Nombre visible</dt><dd class="mt-1 break-words text-sm font-medium">{{ $file->display_name }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Nombre original</dt><dd class="mt-1 break-words text-sm font-medium">{{ $file->original_name }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Extensión</dt><dd class="mt-1 text-sm font-medium uppercase">{{ $file->extension ?: 'No disponible' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">MIME</dt><dd class="mt-1 break-all text-sm font-medium">{{ $file->mime_type ?: 'No disponible' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Tamaño</dt><dd class="mt-1 text-sm font-medium">{{ \Illuminate\Support\Number::fileSize($file->size_bytes, precision: 2) }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Clasificación</dt><dd class="mt-1 text-sm font-medium">{{ $file->visibility->label() }}</dd></div>
            </dl>
        </div>

        <div class="rounded-xl border border-line bg-surface p-5">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-muted">Propiedad y ubicación lógica</h3>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold text-muted">Propietario</dt><dd class="mt-1 text-sm font-medium">{{ trim("{$file->owner?->name} {$file->owner?->last_name}") ?: 'Sin propietario' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Correo</dt><dd class="mt-1 break-all text-sm font-medium">{{ $file->owner?->email ?: 'No disponible' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Departamento</dt><dd class="mt-1 text-sm font-medium">{{ $file->department?->name ?? 'Sin departamento' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Carpeta</dt><dd class="mt-1 text-sm font-medium">{{ $file->folder?->name ?? 'Raíz' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Alcance colaborativo</dt><dd class="mt-1 text-sm font-medium">{{ $file->collaboration_scope?->value ?? 'No aplica' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Colaboradores seleccionados</dt><dd class="mt-1 text-sm font-medium">{{ $file->collaborators->count() }}</dd></div>
            </dl>
        </div>

        <div class="rounded-xl border border-line bg-surface p-5 lg:col-span-2">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-muted">Fechas</h3>
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="text-xs font-semibold text-muted">Carga</dt><dd class="mt-1 text-sm font-medium">{{ $file->uploaded_at?->format('d/m/Y H:i') ?? 'No disponible' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Registro</dt><dd class="mt-1 text-sm font-medium">{{ $file->created_at?->format('d/m/Y H:i') ?? 'No disponible' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Última actualización</dt><dd class="mt-1 text-sm font-medium">{{ $file->updated_at?->format('d/m/Y H:i') ?? 'No disponible' }}</dd></div>
                <div><dt class="text-xs font-semibold text-muted">Eliminación</dt><dd class="mt-1 text-sm font-medium">{{ $file->deleted_at?->format('d/m/Y H:i') ?? 'No aplica' }}</dd></div>
            </dl>
        </div>
    </section>
</x-layouts.admin>
