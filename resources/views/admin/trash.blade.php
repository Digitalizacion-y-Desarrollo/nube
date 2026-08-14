<x-layouts.admin title="Papelera" :user="$user">
    @if (session('status'))
        <x-ui.alert tone="success" class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('admin_trash_error'))
        <x-ui.alert class="mb-5">{{ session('admin_trash_error') }}</x-ui.alert>
    @endif

    @if ($errors->has('confirmation'))
        <x-ui.alert class="mb-5">{{ $errors->first('confirmation') }}</x-ui.alert>
    @endif

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-extrabold">Papelera global</h2>
            <p class="mt-1 text-sm text-muted">Elementos eliminados de todos los usuarios y departamentos. Restaurar y eliminar definitivamente quedan registrados en auditoría.</p>
        </div>
        <span class="inline-flex w-fit rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-300">
            {{ $summary['retention_days'] }} días de retención
        </span>
    </div>

    <section class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Archivos eliminados</span>
            <strong class="mt-2 block text-2xl">{{ number_format($summary['files']) }}</strong>
            <span class="text-xs text-muted">Sujetos a purga automática</span>
        </article>
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Carpetas eliminadas</span>
            <strong class="mt-2 block text-2xl">{{ number_format($summary['folders']) }}</strong>
            <span class="text-xs text-muted">Sólo se purgan manualmente</span>
        </article>
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Almacenamiento retenido</span>
            <strong class="mt-2 block text-2xl">{{ $summary['storage'] }}</strong>
            <span class="text-xs text-muted">Espacio ocupado por la papelera</span>
        </article>
        <article class="rounded-xl border border-line bg-surface p-4">
            <span class="text-xs font-semibold text-muted">Por vencer</span>
            <strong class="mt-2 block text-2xl">{{ number_format($summary['expiring_soon']) }}</strong>
            <span class="text-xs text-muted">Archivos a 7 días o menos de la purga</span>
        </article>
    </section>

    <form action="{{ route('admin.trash') }}" method="GET" class="mb-5 rounded-xl border border-line bg-surface p-4" aria-label="Filtros de la papelera global">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <label class="block xl:col-span-2">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Nombre</span>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar archivo o carpeta" class="h-11 w-full rounded-lg border border-line bg-surface px-3.5 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Usuario</span>
                <select name="user_id" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="">Todos</option>
                    @foreach ($people as $person)
                        <option value="{{ $person->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $person->id)>{{ trim("{$person->name} {$person->last_name}") }} — {{ $person->email }}</option>
                    @endforeach
                </select>
                <span class="mt-1 block text-[11px] text-muted">Propietario o quien eliminó</span>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Departamento</span>
                <select name="department_id" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="">Todos</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="grid grid-cols-2 gap-2">
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-muted">Desde</span>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-muted">Hasta</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                </label>
            </div>
        </div>
        <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:items-end sm:justify-between">
            <label class="block sm:w-44">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Resultados por página</span>
                <select name="per_page" class="h-10 w-full rounded-lg border border-line bg-surface px-3 text-sm outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    @foreach ([10, 20, 50] as $size)
                        <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 20) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <a href="{{ route('admin.trash') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-line px-4 text-sm font-semibold text-muted hover:bg-soft">Limpiar</a>
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-brand px-5 text-sm font-semibold text-white hover:bg-brand/90">Aplicar filtros</button>
            </div>
        </div>
    </form>

    @unless ($canOperate)
        <x-ui.alert tone="info" class="mb-5">
            Puedes consultar la papelera global. Para restaurar o eliminar definitivamente también necesitas el permiso <span class="font-semibold">nube_administracion_administrar</span>.
        </x-ui.alert>
    @endunless

    <section class="mb-6 overflow-hidden rounded-xl border border-line bg-surface">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-5 py-4">
            <div>
                <h3 class="font-bold">Archivos eliminados</h3>
                <p class="mt-0.5 text-xs text-muted">Se purgan automáticamente al cumplir {{ $retentionDays }} días en la papelera.</p>
            </div>
            <span class="text-xs font-semibold text-muted">{{ number_format($trashedFiles->total()) }} {{ $trashedFiles->total() === 1 ? 'archivo' : 'archivos' }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1080px] text-left text-sm">
                <thead class="bg-surface-alt text-[10px] uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-5 py-3">Archivo</th>
                        <th class="px-5 py-3">Propietario</th>
                        <th class="px-5 py-3">Departamento</th>
                        <th class="px-5 py-3">Clasificación</th>
                        <th class="px-5 py-3">Eliminado por</th>
                        <th class="px-5 py-3">Eliminado</th>
                        <th class="px-5 py-3">Purga</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($trashedFiles as $file)
                        <tr class="hover:bg-warm">
                            <td class="px-5 py-3.5">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-red-50 dark:bg-red-500/10">
                                        <x-ui.icon name="file-text" :size="18" alt="" />
                                    </span>
                                    <span class="min-w-0">
                                        <strong class="block max-w-[260px] truncate">{{ $file->display_name }}</strong>
                                        <span class="text-xs text-muted">{{ $file->folder?->name ?? 'Raíz' }} · {{ \Illuminate\Support\Number::fileSize($file->size_bytes, precision: 1) }}</span>
                                    </span>
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-muted">{{ trim("{$file->owner?->name} {$file->owner?->last_name}") ?: 'Sin propietario' }}</td>
                            <td class="px-5 py-3.5 text-muted">{{ $file->department?->name ?? 'Sin departamento' }}</td>
                            <td class="px-5 py-3.5">{{ $file->visibility->label() }}</td>
                            <td class="px-5 py-3.5">
                                @if ($file->deletedBy)
                                    <span class="block font-medium">{{ trim("{$file->deletedBy->name} {$file->deletedBy->last_name}") }}</span>
                                    <span class="block text-xs text-muted">{{ $file->deletedBy->email }}</span>
                                @else
                                    <span class="text-xs text-muted">Sin registro</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <span class="block text-xs font-medium">{{ $file->deleted_at?->format('d/m/Y H:i') }}</span>
                                <span class="text-[11px] text-muted">{{ $file->deleted_at?->diffForHumans() }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <span class="block text-xs font-medium">{{ $file->expires_at?->format('d/m/Y') }}</span>
                                <span class="text-[11px] text-muted">{{ $file->expires_at?->isPast() ? 'Pendiente de purga' : 'Vence '.$file->expires_at?->diffForHumans() }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @if ($canOperate)
                                    <div class="flex justify-end gap-2">
                                        <form action="{{ route('admin.trash.files.restore', $file->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 items-center rounded-lg border border-line px-3 text-xs font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white">Restaurar</button>
                                        </form>
                                        <button type="button" data-modal-open="purge-file-{{ $file->id }}" class="inline-flex h-9 items-center rounded-lg border border-red-200 px-3 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-500/10">Eliminar</button>
                                    </div>
                                @else
                                    <span class="text-xs text-muted">Sólo consulta</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-14 text-center">
                                <span class="block text-sm font-semibold">No hay archivos eliminados</span>
                                <span class="mt-1 block text-xs text-muted">Ajusta los filtros o revisa más adelante.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($trashedFiles->hasPages())
            <div class="border-t border-line px-5 py-4">{{ $trashedFiles->onEachSide(1)->links() }}</div>
        @endif
    </section>

    <section class="overflow-hidden rounded-xl border border-line bg-surface">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-5 py-4">
            <div>
                <h3 class="font-bold">Carpetas eliminadas</h3>
                <p class="mt-0.5 text-xs text-muted">Las carpetas no se purgan automáticamente y sólo pueden eliminarse cuando ya no retienen archivos ni subcarpetas.</p>
            </div>
            <span class="text-xs font-semibold text-muted">{{ number_format($trashedFolders->total()) }} {{ $trashedFolders->total() === 1 ? 'carpeta' : 'carpetas' }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead class="bg-surface-alt text-[10px] uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-5 py-3">Carpeta</th>
                        <th class="px-5 py-3">Propietario</th>
                        <th class="px-5 py-3">Departamento</th>
                        <th class="px-5 py-3">Contenido retenido</th>
                        <th class="px-5 py-3">Eliminada por</th>
                        <th class="px-5 py-3">Eliminada</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($trashedFolders as $folder)
                        @php
                            $retained = $folder->trashed_files_count + $folder->trashed_children_count;
                        @endphp
                        <tr class="hover:bg-warm">
                            <td class="px-5 py-3.5">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-red-50 dark:bg-red-500/10">
                                        <x-ui.icon name="folder" :size="18" alt="" />
                                    </span>
                                    <span class="min-w-0">
                                        <strong class="block max-w-[260px] truncate">{{ $folder->name }}</strong>
                                        <span class="block max-w-[260px] truncate text-xs text-muted">{{ $folder->path_cache ?: '/'.$folder->name }}</span>
                                    </span>
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-muted">{{ trim("{$folder->owner?->name} {$folder->owner?->last_name}") ?: 'Sin propietario' }}</td>
                            <td class="px-5 py-3.5 text-muted">{{ $folder->department?->name ?? 'Sin departamento' }}</td>
                            <td class="px-5 py-3.5">
                                @if ($retained > 0)
                                    <span class="block font-semibold">{{ number_format($retained) }}</span>
                                    <span class="text-[11px] text-muted">{{ number_format($folder->trashed_files_count) }} archivos · {{ number_format($folder->trashed_children_count) }} subcarpetas</span>
                                @else
                                    <span class="text-xs text-muted">Vacía</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                @if ($folder->deletedBy)
                                    <span class="block font-medium">{{ trim("{$folder->deletedBy->name} {$folder->deletedBy->last_name}") }}</span>
                                    <span class="block text-xs text-muted">{{ $folder->deletedBy->email }}</span>
                                @else
                                    <span class="text-xs text-muted">Sin registro</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <span class="block text-xs font-medium">{{ $folder->deleted_at?->format('d/m/Y H:i') }}</span>
                                <span class="text-[11px] text-muted">{{ $folder->deleted_at?->diffForHumans() }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @if ($canOperate)
                                    <div class="flex justify-end gap-2">
                                        <form action="{{ route('admin.trash.folders.restore', $folder->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 items-center rounded-lg border border-line px-3 text-xs font-semibold text-brand hover:border-gold hover:bg-warm dark:text-white">Restaurar</button>
                                        </form>
                                        <button type="button" data-modal-open="purge-folder-{{ $folder->id }}" @disabled($retained > 0) class="inline-flex h-9 items-center rounded-lg border border-red-200 px-3 text-xs font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-500/10">Eliminar</button>
                                    </div>
                                @else
                                    <span class="text-xs text-muted">Sólo consulta</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                <span class="block text-sm font-semibold">No hay carpetas eliminadas</span>
                                <span class="mt-1 block text-xs text-muted">Ajusta los filtros o revisa más adelante.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($trashedFolders->hasPages())
            <div class="border-t border-line px-5 py-4">{{ $trashedFolders->onEachSide(1)->links() }}</div>
        @endif
    </section>

    @if ($canOperate)
        @foreach ($trashedFiles as $file)
            <x-ui.modal :id="'purge-file-'.$file->id" title="Eliminar archivo definitivamente">
                <p class="text-sm leading-6 text-muted">
                    El archivo <span class="font-semibold text-ink">«{{ $file->display_name }}»</span> y su copia física se eliminarán sin posibilidad de recuperación. La operación quedará registrada en auditoría.
                </p>
                <form action="{{ route('admin.trash.files.purge', $file->id) }}" method="POST" class="mt-5">
                    @csrf
                    @method('DELETE')
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold text-muted">Escribe <span class="font-bold text-ink">{{ $file->display_name }}</span> para confirmar</span>
                        <input type="text" name="confirmation" required autocomplete="off" class="h-11 w-full rounded-lg border border-line bg-surface px-3.5 text-sm outline-none focus:border-red-500 focus:ring-3 focus:ring-red-500/10">
                    </label>
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <x-ui.button type="button" variant="secondary" data-modal-close="purge-file-{{ $file->id }}">Cancelar</x-ui.button>
                        <x-ui.button type="submit" variant="danger">Eliminar definitivamente</x-ui.button>
                    </div>
                </form>
            </x-ui.modal>
        @endforeach

        @foreach ($trashedFolders as $folder)
            <x-ui.modal :id="'purge-folder-'.$folder->id" title="Eliminar carpeta definitivamente">
                <p class="text-sm leading-6 text-muted">
                    La carpeta <span class="font-semibold text-ink">«{{ $folder->name }}»</span> se eliminará sin posibilidad de recuperación. La operación quedará registrada en auditoría.
                </p>
                <form action="{{ route('admin.trash.folders.purge', $folder->id) }}" method="POST" class="mt-5">
                    @csrf
                    @method('DELETE')
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold text-muted">Escribe <span class="font-bold text-ink">{{ $folder->name }}</span> para confirmar</span>
                        <input type="text" name="confirmation" required autocomplete="off" class="h-11 w-full rounded-lg border border-line bg-surface px-3.5 text-sm outline-none focus:border-red-500 focus:ring-3 focus:ring-red-500/10">
                    </label>
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <x-ui.button type="button" variant="secondary" data-modal-close="purge-folder-{{ $folder->id }}">Cancelar</x-ui.button>
                        <x-ui.button type="submit" variant="danger">Eliminar definitivamente</x-ui.button>
                    </div>
                </form>
            </x-ui.modal>
        @endforeach
    @endif
</x-layouts.admin>
