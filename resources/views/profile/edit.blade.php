@php
    $maxMegabytes = round($maxSizeKb / 1024, 1);
    $extensionLabel = strtoupper(implode(', ', array_unique($allowedExtensions)));
    $accept = collect($allowedExtensions)->map(fn (string $extension): string => ".{$extension}")->implode(',');
@endphp

<x-layouts.app title="Mi perfil" :user="$user" :permissions="$permissions">
    @if (session('status'))
        <x-ui.alert tone="success" class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('profile_error'))
        <x-ui.alert class="mb-5">{{ session('profile_error') }}</x-ui.alert>
    @endif

    @error('avatar')
        <x-ui.alert class="mb-5">{{ $message }}</x-ui.alert>
    @enderror

    <div class="mb-5">
        <h2 class="text-xl font-extrabold">Mi perfil</h2>
        <p class="mt-1 text-sm text-muted">Puedes cambiar tu foto de perfil. El resto de tus datos proviene del sistema de Accesos.</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1.1fr_1fr]">
        <section class="rounded-xl border border-line bg-surface p-5 sm:p-6">
            <h3 class="text-sm font-bold uppercase tracking-wide text-muted">Foto de perfil</h3>

            <div class="mt-5 flex flex-col items-center gap-5 sm:flex-row sm:items-start">
                <div class="shrink-0 text-center">
                    <img
                        src="{{ $user['avatar'] }}"
                        data-avatar-preview
                        data-avatar-current="{{ $user['avatar'] }}"
                        alt="Foto de perfil actual de {{ $user['name'] }}"
                        width="128"
                        height="128"
                        class="size-32 rounded-full object-cover ring-1 ring-line"
                    >
                    <p data-avatar-preview-badge hidden class="mt-2 inline-flex rounded-full bg-gold/20 px-2.5 py-1 text-[11px] font-semibold text-brand dark:text-white">
                        Vista previa
                    </p>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold">
                        @if ($hasAvatar)
                            Tienes una foto personalizada.
                        @else
                            Estás usando tus iniciales <span class="font-bold">{{ $profileUser->initials() }}</span> como foto predeterminada.
                        @endif
                    </p>
                    <p class="mt-1 text-xs leading-5 text-muted">
                        Formatos aceptados: {{ $extensionLabel }}. Tamaño máximo: {{ $maxMegabytes }} MB.
                        La imagen se guarda en el almacenamiento privado y sólo tú puedes verla desde tu sesión.
                    </p>

                    <form
                        action="{{ route('profile.avatar.update') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="mt-4 space-y-3"
                        data-avatar-form
                        data-avatar-max-kb="{{ $maxSizeKb }}"
                        data-avatar-extensions="{{ implode(',', $allowedExtensions) }}"
                    >
                        @csrf
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold text-muted">Selecciona una imagen</span>
                            <input
                                type="file"
                                name="avatar"
                                accept="{{ $accept }}"
                                required
                                data-avatar-input
                                class="block w-full cursor-pointer rounded-lg border border-line bg-surface text-sm text-muted file:mr-3 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-brand file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-brand/90"
                            >
                        </label>

                        <p data-avatar-file-info hidden class="text-xs text-muted"></p>
                        <p data-avatar-file-error hidden role="alert" class="text-xs font-semibold text-red-700 dark:text-red-300"></p>

                        <div class="flex flex-col-reverse gap-2 sm:flex-row">
                            <x-ui.button type="submit" data-avatar-submit>Guardar foto</x-ui.button>
                            <x-ui.button type="button" variant="secondary" data-avatar-cancel hidden>Descartar selección</x-ui.button>
                        </div>
                    </form>

                    @if ($hasAvatar)
                        <form action="{{ route('profile.avatar.destroy') }}" method="POST" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="secondary">Volver a mis iniciales</x-ui.button>
                        </form>
                    @endif
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-line bg-surface p-5 sm:p-6">
            <h3 class="text-sm font-bold uppercase tracking-wide text-muted">Datos de tu cuenta</h3>
            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold text-muted">Nombre</dt>
                    <dd class="mt-1 text-sm font-medium">{{ $user['name'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Correo</dt>
                    <dd class="mt-1 break-all text-sm font-medium">{{ $profileUser->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Departamento</dt>
                    <dd class="mt-1 text-sm font-medium">{{ $user['department'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-muted">Estado</dt>
                    <dd class="mt-1 text-sm font-medium">{{ $profileUser->active ? 'Activo' : 'Inactivo' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-muted">Roles</dt>
                    <dd class="mt-2 flex flex-wrap gap-1.5">
                        @forelse ($profileUser->roles as $role)
                            <span class="rounded-full bg-brand/8 px-2.5 py-1 text-[11px] font-semibold text-brand dark:text-white">{{ $role->display_name ?: $role->name }}</span>
                        @empty
                            <span class="text-xs text-muted">Sin roles asignados</span>
                        @endforelse
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-muted">Última sincronización</dt>
                    <dd class="mt-1 text-sm font-medium">{{ $profileUser->last_synced_at?->format('d/m/Y H:i') ?? 'Sin sincronizar' }}</dd>
                </div>
            </dl>

            <x-ui.alert tone="info" class="mt-5">
                Tu nombre, correo, departamento, roles y permisos se administran en el sistema de Accesos. Aquí sólo puedes cambiar tu foto.
            </x-ui.alert>
        </section>
    </div>
</x-layouts.app>
