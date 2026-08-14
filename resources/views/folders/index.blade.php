<x-layouts.app :title="$title" :user="$user" :permissions="$permissions">
    <x-slot:uploadModal>
        @if ($canUploadFile)
            <x-ui.modal
                id="upload-modal"
                :title="$currentFolder ? 'Agregar archivo en '.$currentFolder->name : 'Subir archivo'"
                :data-modal-auto-open="$errors->uploadFile->any() || $autoOpenModal === 'upload' ? 'true' : null"
            >
                <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4" data-file-upload-form data-sharing-form>
                    @csrf

                    <label class="block">
                        <span class="mb-1.5 block text-[13px] font-semibold text-muted">Archivo</span>
                        <input
                            type="file"
                            name="file"
                            required
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.jpg,.jpeg,.png,.zip"
                            class="block w-full rounded-lg border border-line bg-surface text-sm text-ink file:mr-4 file:border-0 file:bg-brand file:px-4 file:py-3 file:font-semibold file:text-white hover:file:bg-brand-dark"
                        >
                    </label>

                    <div class="rounded-xl border border-line bg-surface-alt px-3.5 py-3">
                        <input type="hidden" name="visibility" data-sharing-visibility value="{{ $defaultUploadVisibility }}">
                        <span class="block text-[11px] font-semibold uppercase tracking-wide text-muted">Clasificación</span>
                        <span class="mt-1 block text-sm font-semibold text-ink">
                            {{ \App\Enums\FileVisibility::from($defaultUploadVisibility)->label() }}
                        </span>
                        <span class="mt-1 block text-xs text-muted">
                            Determinada por la sección actual. Podrás cambiarla después desde Reclasificar.
                        </span>
                    </div>

                    @if ($currentFolder)
                        <p class="rounded-lg bg-soft px-3 py-2.5 text-xs leading-5 text-muted">
                            La clasificación y los colaboradores se heredan de
                            <span class="font-semibold text-ink">{{ $currentFolder->name }}</span>.
                            Puedes cambiarlos antes de subir el archivo.
                        </p>
                    @endif

                    <label class="block">
                        <span class="mb-1.5 block text-[13px] font-semibold text-muted">Carpeta de destino</span>
                        <select name="folder_id" class="h-[46px] w-full rounded-lg border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                            <option value="" @selected(old('folder_id', $currentFolder?->id) === null)>Raíz de la clasificación</option>
                            @foreach ($creationDestinationFolders as $destinationFolder)
                                <option value="{{ $destinationFolder->id }}" @selected(old('folder_id', $currentFolder?->id) === $destinationFolder->id)>
                                    {{ $destinationFolder->path_cache ?: '/'.$destinationFolder->name }} · {{ $destinationFolder->visibility->label() }}
                                </option>
                            @endforeach
                        </select>
                        @if ($currentFolder)
                            <span class="mt-1.5 block text-xs text-muted">
                                Ubicación actual preseleccionada: {{ $logicalPath }}
                            </span>
                        @endif
                    </label>

                    @include('folders.partials.collaboration-fields', [
                        'contextId' => null,
                        'pickerId' => 'upload-collaborators',
                        'defaultCollaborationScope' => $defaultUploadCollaborationScope,
                        'defaultCollaborators' => $defaultUploadCollaborators,
                        'defaultCollaboratorPermissions' => $defaultUploadCollaboratorPermissions,
                        'errorBag' => $errors->getBag('uploadFile'),
                    ])

                    @if ($errors->uploadFile->has('file'))
                        <x-ui.alert>{{ $errors->uploadFile->first('file') }}</x-ui.alert>
                    @endif
                    @if ($errors->uploadFile->has('folder_id'))
                        <x-ui.alert>{{ $errors->uploadFile->first('folder_id') }}</x-ui.alert>
                    @endif
                    @if ($errors->uploadFile->has('visibility'))
                        <x-ui.alert>{{ $errors->uploadFile->first('visibility') }}</x-ui.alert>
                    @endif

                    <div class="rounded-lg bg-soft px-3 py-2.5 text-xs leading-5 text-muted">
                        <p>Máximo: 200 MB. PDF, Office, TXT, CSV, JPG, PNG o ZIP.</p>
                    </div>

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <x-ui.button type="button" variant="secondary" data-modal-close="upload-modal">
                            Cancelar
                        </x-ui.button>
                        <x-ui.button type="submit" data-file-upload-submit>
                            <x-ui.icon name="upload-cloud" :size="18" alt="" />
                            <span data-file-upload-label>Subir archivo</span>
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.modal>
        @endif
    </x-slot:uploadModal>

    <x-slot:folderModal>
        @if ($canCreateFolder)
            <x-ui.modal
                id="folder-modal"
                :title="$currentFolder ? 'Nueva subcarpeta en '.$currentFolder->name : 'Nueva carpeta'"
                :data-modal-auto-open="$errors->createFolder->any() || $autoOpenModal === 'folder' ? 'true' : null"
            >
                    <form action="{{ route('folders.store') }}" method="POST" class="space-y-4" data-sharing-form>
                    @csrf
                    <x-ui.input
                        name="name"
                        label="Nombre de la carpeta"
                        :value="old('name')"
                        placeholder="Ej. Contratos 2026"
                        maxlength="150"
                        required
                        autofocus
                        :error="$errors->createFolder->first('name')"
                    />

                    <label class="block">
                        <span class="mb-1.5 block text-[13px] font-semibold text-muted">Carpeta de destino</span>
                        <select name="parent_id" class="h-[46px] w-full rounded-lg border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                            <option value="" @selected(old('parent_id', $currentFolder?->id) === null)>Raíz de la clasificación</option>
                            @foreach ($destinationFolders as $destinationFolder)
                                <option value="{{ $destinationFolder->id }}" @selected(old('parent_id', $currentFolder?->id) === $destinationFolder->id)>
                                    {{ $destinationFolder->path_cache ?: '/'.$destinationFolder->name }} · {{ $destinationFolder->visibility->label() }}
                                </option>
                            @endforeach
                        </select>
                        @if ($currentFolder)
                            <span class="mt-1.5 block text-xs text-muted">
                                Ubicación actual preseleccionada: {{ $logicalPath }}
                            </span>
                        @endif
                    </label>

                    <div class="rounded-xl border border-line bg-surface-alt px-3.5 py-3">
                        <input type="hidden" name="visibility" data-sharing-visibility value="{{ $defaultFolderVisibility }}">
                        <span class="block text-[11px] font-semibold uppercase tracking-wide text-muted">Clasificación</span>
                        <span class="mt-1 block text-sm font-semibold text-ink">
                            {{ \App\Enums\FileVisibility::from($defaultFolderVisibility)->label() }}
                        </span>
                        <span class="mt-1 block text-xs text-muted">
                            Determinada por la sección actual. Podrás cambiarla después desde Reclasificar.
                        </span>
                    </div>

                    @include('folders.partials.collaboration-fields', [
                        'contextId' => null,
                        'pickerId' => 'folder-collaborators',
                        'errorBag' => $errors->getBag('createFolder'),
                    ])

                    @if ($errors->createFolder->has('parent_id'))
                        <x-ui.alert>{{ $errors->createFolder->first('parent_id') }}</x-ui.alert>
                    @endif
                    @if ($errors->createFolder->has('visibility'))
                        <x-ui.alert>{{ $errors->createFolder->first('visibility') }}</x-ui.alert>
                    @endif

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <x-ui.button type="button" variant="secondary" data-modal-close="folder-modal">
                            Cancelar
                        </x-ui.button>
                        <x-ui.button type="submit">
                            <x-ui.icon name="folder-plus" :size="18" alt="" />
                            Crear carpeta
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.modal>
        @endif

        @foreach ($items->where('type', 'folder') as $folderItem)
            @if ($folderItem['can_rename'])
                <x-ui.modal
                    :id="'rename-folder-'.$folderItem['id']"
                    title="Renombrar carpeta"
                    :data-modal-auto-open="$errors->renameFolder->any() && old('folder_context') === $folderItem['id'] ? 'true' : null"
                >
                    <form action="{{ $folderItem['update_url'] }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="folder_context" value="{{ $folderItem['id'] }}">

                        <x-ui.input
                            name="name"
                            label="Nuevo nombre"
                            :value="old('folder_context') === $folderItem['id'] ? old('name') : $folderItem['name']"
                            maxlength="150"
                            required
                            :error="old('folder_context') === $folderItem['id'] ? $errors->renameFolder->first('name') : null"
                        />

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <x-ui.button type="button" variant="secondary" :data-modal-close="'rename-folder-'.$folderItem['id']">
                                Cancelar
                            </x-ui.button>
                            <x-ui.button type="submit">Guardar nombre</x-ui.button>
                        </div>
                    </form>
                </x-ui.modal>
            @endif

            @if ($folderItem['can_move'])
                <x-ui.modal
                    :id="$folderItem['move_modal_id']"
                    title="Mover carpeta"
                    :data-modal-auto-open="$errors->moveFolder->any() && old('folder_context') === $folderItem['id'] ? 'true' : null"
                >
                    <form action="{{ $folderItem['move_url'] }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="folder_context" value="{{ $folderItem['id'] }}">

                        <label class="block">
                            <span class="mb-1.5 block text-[13px] font-semibold text-muted">Carpeta de destino</span>
                            <select name="destination_folder_id" class="h-[46px] w-full rounded-lg border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                                <option value="" @disabled($folderItem['parent_id'] === null)>
                                    {{ $folderItem['visibility_label'] }} / raíz
                                </option>
                                @foreach ($destinationFolders->where('visibility', \App\Enums\FileVisibility::from($folderItem['visibility'])) as $destinationFolder)
                                    @continue($destinationFolder->id === $folderItem['id'])
                                    <option
                                        value="{{ $destinationFolder->id }}"
                                        @selected(old('folder_context') === $folderItem['id'] && old('destination_folder_id') === $destinationFolder->id)
                                        @disabled($folderItem['parent_id'] === $destinationFolder->id)
                                    >
                                        {{ $destinationFolder->path_cache ?: '/'.$destinationFolder->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        @if (old('folder_context') === $folderItem['id'] && $errors->moveFolder->has('destination_folder_id'))
                            <x-ui.alert>{{ $errors->moveFolder->first('destination_folder_id') }}</x-ui.alert>
                        @endif

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <x-ui.button type="button" variant="secondary" :data-modal-close="$folderItem['move_modal_id']">
                                Cancelar
                            </x-ui.button>
                            <x-ui.button type="submit">Mover carpeta</x-ui.button>
                        </div>
                    </form>
                </x-ui.modal>
            @endif

            @if ($folderItem['can_delete'])
                <x-ui.modal :id="'delete-folder-'.$folderItem['id']" title="Eliminar carpeta">
                    <p class="text-sm leading-6 text-muted">
                        La carpeta <span class="font-semibold text-ink">«{{ $folderItem['name'] }}»</span>
                        se enviará a la papelera. Solo es posible eliminar carpetas vacías.
                    </p>
                    <form action="{{ $folderItem['delete_url'] }}" method="POST" class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="button" variant="secondary" :data-modal-close="'delete-folder-'.$folderItem['id']">
                            Cancelar
                        </x-ui.button>
                        <x-ui.button type="submit" variant="danger">Enviar a papelera</x-ui.button>
                    </form>
                </x-ui.modal>
            @endif

            @if ($folderItem['can_change_visibility'])
                <x-ui.modal
                    :id="$folderItem['visibility_modal_id']"
                    title="Reclasificar carpeta"
                    :data-modal-auto-open="$errors->changeFolderVisibility->any() && old('folder_context') === $folderItem['id'] ? 'true' : null"
                >
                    <form action="{{ $folderItem['visibility_url'] }}" method="POST" class="space-y-4" data-sharing-form>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="folder_context" value="{{ $folderItem['id'] }}">

                        <p class="text-sm leading-6 text-muted">
                            Clasificación actual:
                            <span class="font-semibold text-ink">{{ $folderItem['visibility_label'] }}</span>.
                            Los archivos y subcarpetas conservarán su propia clasificación.
                        </p>

                        <label class="block">
                            <span class="mb-1.5 block text-[13px] font-semibold text-muted">Nueva clasificación</span>
                            <select name="visibility" data-sharing-visibility required class="h-[46px] w-full rounded-lg border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                                <option
                                    value=""
                                    disabled
                                    @selected(! (old('folder_context') === $folderItem['id'] && old('visibility')))
                                >
                                    {{ $folderItem['visibility_label'] }} (actual)
                                </option>
                                @foreach ($folderItem['visibility_options'] as $visibilityOption)
                                    <option value="{{ $visibilityOption['value'] }}" @selected(old('folder_context') === $folderItem['id'] && old('visibility') === $visibilityOption['value'])>
                                        {{ $visibilityOption['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        @include('folders.partials.collaboration-fields', [
                            'contextId' => $folderItem['id'],
                            'contextField' => 'folder_context',
                            'pickerId' => 'folder-visibility-collaborators-'.$folderItem['id'],
                            'errorBag' => $errors->getBag('changeFolderVisibility'),
                        ])

                        @if (old('folder_context') === $folderItem['id'] && $errors->changeFolderVisibility->has('visibility'))
                            <x-ui.alert>{{ $errors->changeFolderVisibility->first('visibility') }}</x-ui.alert>
                        @endif

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <x-ui.button type="button" variant="secondary" :data-modal-close="$folderItem['visibility_modal_id']">
                                Cancelar
                            </x-ui.button>
                            <x-ui.button type="submit">Reclasificar carpeta</x-ui.button>
                        </div>
                    </form>
                </x-ui.modal>
            @endif
        @endforeach

        @foreach ($items->where('type', 'file') as $fileItem)
            @if ($fileItem['can_rename'])
                <x-ui.modal
                    :id="$fileItem['rename_modal_id']"
                    title="Renombrar archivo"
                    :data-modal-auto-open="$errors->renameFile->any() && old('file_context') === $fileItem['id'] ? 'true' : null"
                >
                    <form action="{{ $fileItem['update_url'] }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="file_context" value="{{ $fileItem['id'] }}">

                        <x-ui.input
                            name="display_name"
                            label="Nuevo nombre visible"
                            :value="old('file_context') === $fileItem['id'] ? old('display_name') : $fileItem['name']"
                            maxlength="255"
                            required
                            :error="old('file_context') === $fileItem['id'] ? $errors->renameFile->first('display_name') : null"
                        />

                        <p class="text-xs leading-5 text-muted">El nombre físico seguro no será modificado.</p>

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <x-ui.button type="button" variant="secondary" :data-modal-close="$fileItem['rename_modal_id']">
                                Cancelar
                            </x-ui.button>
                            <x-ui.button type="submit">Guardar nombre</x-ui.button>
                        </div>
                    </form>
                </x-ui.modal>
            @endif

            @if ($fileItem['can_move'])
                <x-ui.modal
                    :id="$fileItem['move_modal_id']"
                    title="Mover archivo"
                    :data-modal-auto-open="$errors->moveFile->any() && old('file_context') === $fileItem['id'] ? 'true' : null"
                >
                    <form action="{{ $fileItem['move_url'] }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="file_context" value="{{ $fileItem['id'] }}">

                        <label class="block">
                            <span class="mb-1.5 block text-[13px] font-semibold text-muted">Carpeta de destino</span>
                            <select name="destination_folder_id" class="h-[46px] w-full rounded-lg border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                                <option value="">{{ $fileItem['visibility_label'] }} / raíz</option>
                                @foreach ($destinationFolders->where('visibility', \App\Enums\FileVisibility::from($fileItem['visibility'])) as $destinationFolder)
                                    <option
                                        value="{{ $destinationFolder->id }}"
                                        @selected(old('file_context') === $fileItem['id'] && old('destination_folder_id') === $destinationFolder->id)
                                        @disabled($fileItem['folder_id'] === $destinationFolder->id)
                                    >
                                        {{ $destinationFolder->path_cache ?: '/'.$destinationFolder->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        @if (old('file_context') === $fileItem['id'] && $errors->moveFile->has('destination_folder_id'))
                            <x-ui.alert>{{ $errors->moveFile->first('destination_folder_id') }}</x-ui.alert>
                        @endif

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <x-ui.button type="button" variant="secondary" :data-modal-close="$fileItem['move_modal_id']">
                                Cancelar
                            </x-ui.button>
                            <x-ui.button type="submit">Mover archivo</x-ui.button>
                        </div>
                    </form>
                </x-ui.modal>
            @endif

            @if ($fileItem['can_change_visibility'])
                <x-ui.modal
                    :id="$fileItem['visibility_modal_id']"
                    title="Cambiar clasificación"
                    :data-modal-auto-open="$errors->changeVisibility->any() && old('file_context') === $fileItem['id'] ? 'true' : null"
                >
                    <form action="{{ $fileItem['visibility_url'] }}" method="POST" class="space-y-4" data-sharing-form>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="file_context" value="{{ $fileItem['id'] }}">

                        <p class="text-sm leading-6 text-muted">
                            Clasificación actual:
                            <span class="font-semibold text-ink">{{ $fileItem['visibility_label'] }}</span>.
                            El archivo se moverá a la raíz de la nueva sección.
                        </p>

                        <label class="block">
                            <span class="mb-1.5 block text-[13px] font-semibold text-muted">Nueva clasificación</span>
                            <select name="visibility" data-sharing-visibility required class="h-[46px] w-full rounded-lg border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                                <option
                                    value=""
                                    disabled
                                    @selected(! (old('file_context') === $fileItem['id'] && old('visibility')))
                                >
                                    {{ $fileItem['visibility_label'] }} (actual)
                                </option>
                                @foreach ($fileItem['visibility_options'] as $visibilityOption)
                                    <option value="{{ $visibilityOption['value'] }}" @selected(old('file_context') === $fileItem['id'] && old('visibility') === $visibilityOption['value'])>
                                        {{ $visibilityOption['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        @include('folders.partials.collaboration-fields', [
                            'contextId' => $fileItem['id'],
                            'pickerId' => 'file-visibility-collaborators-'.$fileItem['id'],
                            'errorBag' => $errors->getBag('changeVisibility'),
                        ])

                        @if (old('file_context') === $fileItem['id'] && $errors->changeVisibility->has('visibility'))
                            <x-ui.alert>{{ $errors->changeVisibility->first('visibility') }}</x-ui.alert>
                        @endif

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <x-ui.button type="button" variant="secondary" :data-modal-close="$fileItem['visibility_modal_id']">
                                Cancelar
                            </x-ui.button>
                            <x-ui.button type="submit">Cambiar clasificación</x-ui.button>
                        </div>
                    </form>
                </x-ui.modal>
            @endif

            @if ($fileItem['can_delete'])
                <x-ui.modal :id="$fileItem['delete_modal_id']" title="Eliminar archivo">
                    <p class="text-sm leading-6 text-muted">
                        El archivo <span class="font-semibold text-ink">«{{ $fileItem['name'] }}»</span>
                        se moverá a la papelera y podrá restaurarse posteriormente.
                    </p>
                    <form action="{{ $fileItem['delete_url'] }}" method="POST" class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="button" variant="secondary" :data-modal-close="$fileItem['delete_modal_id']">
                            Cancelar
                        </x-ui.button>
                        <x-ui.button type="submit" variant="danger">Enviar a papelera</x-ui.button>
                    </form>
                </x-ui.modal>
            @endif

            @if ($fileItem['can_restore'])
                <x-ui.modal
                    :id="$fileItem['restore_modal_id']"
                    title="Restaurar archivo"
                    :data-modal-auto-open="$errors->restoreFile->any() && old('file_context') === $fileItem['id'] ? 'true' : null"
                >
                    <form action="{{ $fileItem['restore_url'] }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="file_context" value="{{ $fileItem['id'] }}">

                        <label class="block">
                            <span class="mb-1.5 block text-[13px] font-semibold text-muted">Restaurar en</span>
                            <select name="destination_folder_id" class="h-[46px] w-full rounded-lg border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                                <option value="">{{ $fileItem['visibility_label'] }} / raíz</option>
                                @foreach ($destinationFolders as $destinationFolder)
                                    <option value="{{ $destinationFolder->id }}" @selected(old('destination_folder_id') === $destinationFolder->id)>
                                        {{ $destinationFolder->path_cache ?: '/'.$destinationFolder->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        @if (old('file_context') === $fileItem['id'] && $errors->restoreFile->has('destination_folder_id'))
                            <x-ui.alert>{{ $errors->restoreFile->first('destination_folder_id') }}</x-ui.alert>
                        @endif

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <x-ui.button type="button" variant="secondary" :data-modal-close="$fileItem['restore_modal_id']">
                                Cancelar
                            </x-ui.button>
                            <x-ui.button type="submit">Restaurar archivo</x-ui.button>
                        </div>
                    </form>
                </x-ui.modal>
            @endif
        @endforeach
    </x-slot:folderModal>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('folder_error'))
        <x-ui.alert class="mb-5">{{ session('folder_error') }}</x-ui.alert>
    @endif

    @if (session('file_error'))
        <x-ui.alert class="mb-5">{{ session('file_error') }}</x-ui.alert>
    @endif

    <section class="mb-6">
        <div class="overflow-x-auto pb-1">
            <x-ui.breadcrumbs :items="$breadcrumbs" />
        </div>

        <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    @if ($parentUrl)
                        <a href="{{ $parentUrl }}" class="flex size-10 shrink-0 items-center justify-center rounded-full border border-line bg-surface hover:border-gold hover:bg-warm" aria-label="Volver a la carpeta anterior">
                            <span aria-hidden="true" class="text-xl leading-none">←</span>
                        </a>
                    @endif
                    <div class="min-w-0">
                        <h2 class="truncate text-2xl font-bold text-ink lg:text-[28px]">
                            {{ $currentFolder?->name ?? $title }}
                        </h2>
                        <p class="mt-1 text-sm text-muted">{{ $description }}</p>
                    </div>
                </div>
                <p class="mt-3 truncate font-mono text-xs text-muted" title="{{ $logicalPath }}">
                    Ruta: {{ $logicalPath }}
                </p>
            </div>

            @if ($canUploadFile || $canCreateFolder)
                <div
                    @if ($currentFolder) data-current-folder-actions @endif
                    class="w-full shrink-0 sm:w-auto"
                    @if ($currentFolder) aria-label="Agregar contenido en {{ $currentFolder->name }}" @endif
                >
                    @if ($currentFolder)
                        <p class="mb-2 text-xs font-semibold text-muted sm:text-right">
                            Agregar en esta carpeta
                        </p>
                    @endif
                    <div class="flex flex-col gap-2 sm:flex-row">
                        @if ($canUploadFile)
                            <x-ui.button data-modal-open="upload-modal" class="w-full sm:w-auto">
                                <x-ui.icon name="upload-cloud" :size="18" alt="" />
                                <span>{{ $currentFolder ? 'Agregar archivo' : 'Subir archivo' }}</span>
                            </x-ui.button>
                        @endif
                        @if ($canCreateFolder)
                            <x-ui.button variant="secondary" data-modal-open="folder-modal" class="w-full sm:w-auto">
                                <x-ui.icon name="folder-plus" :size="18" alt="" />
                                <span>{{ $currentFolder ? 'Nueva subcarpeta' : 'Nueva carpeta' }}</span>
                            </x-ui.button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>

    @if ($section === 'trash')
        <x-ui.alert tone="warning" class="mb-5">
            Los archivos se eliminan permanentemente {{ $trashRetentionDays }} días después de enviarse a la Papelera.
        </x-ui.alert>
    @endif

    <section aria-label="Resumen de la ubicación" class="mb-5 grid grid-cols-2 gap-3 sm:max-w-md">
        <article class="rounded-xl border border-line bg-surface p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted">Carpetas</p>
            <p class="mt-1 text-2xl font-bold text-brand dark:text-white">{{ $folderCount }}</p>
        </article>
        <article class="rounded-xl border border-line bg-surface p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted">Archivos</p>
            <p class="mt-1 text-2xl font-bold text-brand dark:text-white">{{ $fileCount }}</p>
        </article>
    </section>

    <section aria-labelledby="explorer-filters-title" class="mb-5 rounded-xl border border-line bg-surface p-4 sm:p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 id="explorer-filters-title" class="text-sm font-bold text-ink">Buscar y filtrar</h3>
                <p class="mt-1 text-xs text-muted" aria-live="polite">
                    {{ $filteredItemCount }} de {{ $availableItemCount }} elementos
                </p>
            </div>
            @if (collect($filters)->except(['sort', 'direction', 'per_page'])->filter()->isNotEmpty())
                <a href="{{ url()->current() }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-brand hover:bg-brand/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand dark:text-white">
                    Limpiar filtros
                </a>
            @endif
        </div>

        <form method="GET" action="{{ url()->current() }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
            <label class="xl:col-span-4">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Buscar</span>
                <span class="relative block">
                    <x-ui.icon name="search" :size="17" alt="" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                        type="search"
                        name="q"
                        value="{{ $filters['q'] }}"
                        maxlength="100"
                        placeholder="Nombre de archivo o carpeta"
                        class="h-11 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-sm text-ink outline-none transition placeholder:text-muted focus:border-brand focus:ring-3 focus:ring-brand/10"
                    >
                </span>
            </label>

            <label class="xl:col-span-2">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Tipo</span>
                <select name="type" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="all" @selected($filters['type'] === 'all')>Todos</option>
                    <option value="folder" @selected($filters['type'] === 'folder')>Carpetas</option>
                    <option value="file" @selected($filters['type'] === 'file')>Archivos</option>
                </select>
            </label>

            <label class="xl:col-span-3">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Clasificación</span>
                <select name="visibility" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="">Todas</option>
                    @foreach (\App\Enums\FileVisibility::cases() as $visibility)
                        <option value="{{ $visibility->value }}" @selected($filters['visibility'] === $visibility->value)>
                            {{ $visibility->label() }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="xl:col-span-3">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Propietario</span>
                <select name="owner_id" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="">Todos</option>
                    @foreach ($ownerOptions as $ownerOption)
                        <option value="{{ $ownerOption['id'] }}" @selected((string) $filters['owner_id'] === (string) $ownerOption['id'])>
                            {{ $ownerOption['name'] }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="xl:col-span-2">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Desde</span>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
            </label>

            <label class="xl:col-span-2">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Hasta</span>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
            </label>

            <label class="xl:col-span-2">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Ordenar por</span>
                <select name="sort" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="name" @selected($filters['sort'] === 'name')>Nombre</option>
                    <option value="date" @selected($filters['sort'] === 'date')>Fecha</option>
                    <option value="size" @selected($filters['sort'] === 'size')>Tamaño</option>
                </select>
            </label>

            <label class="xl:col-span-2">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Dirección</span>
                <select name="direction" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    <option value="asc" @selected($filters['direction'] === 'asc')>Ascendente</option>
                    <option value="desc" @selected($filters['direction'] === 'desc')>Descendente</option>
                </select>
            </label>

            <label class="xl:col-span-2">
                <span class="mb-1.5 block text-xs font-semibold text-muted">Por página</span>
                <select name="per_page" class="h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm text-ink outline-none focus:border-brand focus:ring-3 focus:ring-brand/10">
                    @foreach ([10, 25, 50] as $perPage)
                        <option value="{{ $perPage }}" @selected((int) $filters['per_page'] === $perPage)>{{ $perPage }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex items-end xl:col-span-2">
                <x-ui.button type="submit" class="h-11 w-full">
                    Aplicar
                </x-ui.button>
            </div>
        </form>
    </section>

    <section aria-labelledby="explorer-items-title" class="overflow-hidden rounded-xl border border-line bg-surface">
        <div class="flex items-center justify-between border-b border-line px-4 py-4 sm:px-5">
            <h3 id="explorer-items-title" class="text-sm font-bold">
                {{ $section === 'trash' ? 'Elementos eliminados' : 'Contenido' }}
            </h3>
            <span class="text-xs text-muted">{{ $filteredItemCount }} elementos</span>
        </div>

        @if ($items->isEmpty())
            <div class="flex min-h-72 flex-col items-center justify-center px-6 py-12 text-center">
                <span class="flex size-16 items-center justify-center rounded-2xl bg-brand/5">
                    <x-ui.icon :name="$section === 'trash' ? 'trash' : 'folder-open'" :size="32" alt="" />
                </span>
                <h4 class="mt-4 text-base font-bold">
                    @if ($availableItemCount > 0)
                        No encontramos coincidencias
                    @else
                        {{ $section === 'trash' ? 'La papelera está vacía' : 'Aún no hay contenido aquí' }}
                    @endif
                </h4>
                <p class="mt-1 max-w-md text-sm leading-6 text-muted">
                    @if ($availableItemCount > 0)
                        Ajusta los filtros o limpia la búsqueda para volver a ver todo el contenido.
                    @elseif ($section === 'mine')
                        Crea una carpeta para comenzar a organizar tus archivos privados.
                    @elseif ($section === 'department')
                        El contenido colaborativo de tu departamento aparecerá en esta ubicación.
                    @elseif ($section === 'public')
                        Los archivos publicados para toda la organización aparecerán aquí.
                    @else
                        Los archivos y carpetas que elimines aparecerán temporalmente en esta sección.
                    @endif
                </p>
                @if ($availableItemCount > 0)
                    <a href="{{ url()->current() }}" class="mt-5 inline-flex rounded-lg border border-line px-4 py-2.5 text-sm font-semibold text-brand hover:bg-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand dark:text-white">
                        Limpiar filtros
                    </a>
                @elseif ($canUploadFile || $canCreateFolder)
                    <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                        @if ($canUploadFile)
                            <x-ui.button data-modal-open="upload-modal">
                                <x-ui.icon name="upload-cloud" :size="18" alt="" />
                                <span>{{ $currentFolder ? 'Agregar archivo' : 'Subir archivo' }}</span>
                            </x-ui.button>
                        @endif
                        @if ($canCreateFolder)
                            <x-ui.button variant="secondary" data-modal-open="folder-modal">
                                <x-ui.icon name="folder-plus" :size="18" alt="" />
                                <span>{{ $currentFolder ? 'Nueva subcarpeta' : 'Crear carpeta' }}</span>
                            </x-ui.button>
                        @endif
                    </div>
                @endif
            </div>
        @else
            <div class="divide-y divide-line lg:hidden">
                @foreach ($items as $item)
                    <article class="flex items-center gap-3 p-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand/5">
                            <x-ui.icon :name="$item['icon']" :size="22" alt="" />
                        </span>
                        <span class="min-w-0 flex-1">
                            @if ($item['url'])
                                <a href="{{ $item['url'] }}" class="block truncate text-sm font-semibold hover:text-brand dark:hover:text-white">
                                    {{ $item['name'] }}
                                </a>
                            @else
                                <span class="block truncate text-sm font-semibold">{{ $item['name'] }}</span>
                            @endif
                            <span class="mt-1 block text-xs text-muted">
                                {{ $item['type'] === 'folder' ? 'Carpeta' : ($item['size'] ?? 'Archivo') }}
                                @if ($item['date'])
                                    · {{ $item['date'] }}
                                @endif
                            </span>
                            @if (! $item['purge_label'])
                                <span class="mt-1 inline-flex rounded-full bg-brand/5 px-2 py-0.5 text-[11px] font-semibold text-brand dark:text-white">
                                    {{ $item['visibility_label'] }}
                                </span>
                                <span class="mt-1 block text-[11px] text-muted">{{ $item['sharing_label'] }}</span>
                            @endif
                            @if ($item['purge_label'])
                                <span class="mt-1 block text-xs font-semibold text-red-700" title="{{ $item['purge_at'] }}">
                                    {{ $item['purge_label'] }}
                                </span>
                            @endif
                        </span>
                        @if ($item['can_download'] || $item['can_rename'] || $item['can_move'] || $item['can_delete'] || $item['can_change_visibility'] || $item['can_restore'] || $item['can_force_delete'])
                            <span class="flex shrink-0 items-center">
                                @if ($item['can_download'])
                                    <a href="{{ $item['download_url'] }}" class="flex size-10 items-center justify-center rounded-full hover:bg-soft" aria-label="Descargar {{ $item['name'] }}">
                                        <x-ui.icon name="arrow-down" :size="17" alt="" />
                                    </a>
                                @endif
                                @if ($item['can_rename'])
                                    <button type="button" data-modal-open="{{ $item['rename_modal_id'] }}" class="flex size-10 items-center justify-center rounded-full text-xs font-semibold text-brand hover:bg-soft dark:text-white" aria-label="Renombrar {{ $item['name'] }}">
                                        Editar
                                    </button>
                                @endif
                                @if ($item['can_move'])
                                    <button type="button" data-modal-open="{{ $item['move_modal_id'] }}" class="flex size-10 items-center justify-center rounded-full hover:bg-soft" aria-label="Mover {{ $item['name'] }}">
                                        <x-ui.icon name="arrow-left-right" :size="17" alt="" />
                                    </button>
                                @endif
                                @if ($item['can_delete'])
                                    <button type="button" data-modal-open="{{ $item['delete_modal_id'] }}" class="flex size-10 items-center justify-center rounded-full hover:bg-red-50" aria-label="Eliminar {{ $item['name'] }}">
                                        <x-ui.icon name="trash" :size="17" alt="" />
                                    </button>
                                @endif
                                @if ($item['can_change_visibility'])
                                    <button type="button" data-modal-open="{{ $item['visibility_modal_id'] }}" class="rounded-lg px-2 py-2 text-xs font-semibold text-brand hover:bg-brand/10 dark:text-white" aria-label="Cambiar clasificación de {{ $item['name'] }}">
                                        Clasificar
                                    </button>
                                @endif
                                @if ($item['can_restore'])
                                    <button type="button" data-modal-open="{{ $item['restore_modal_id'] }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-brand hover:bg-brand/10 dark:text-white">
                                        Restaurar
                                    </button>
                                @endif
                                @if ($item['can_force_delete'])
                                    <form action="{{ $item['force_delete_url'] }}" method="POST" data-permanent-delete-form data-file-name="{{ $item['name'] }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex size-10 items-center justify-center rounded-full text-red-700 hover:bg-red-50" aria-label="Eliminar permanentemente {{ $item['name'] }}">
                                            <x-ui.icon name="trash" :size="17" alt="" />
                                        </button>
                                    </form>
                                @endif
                            </span>
                        @endif
                    </article>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full min-w-[720px] table-fixed text-left">
                    <thead class="bg-surface-alt text-[11px] uppercase text-muted">
                        <tr>
                            <th class="w-[34%] px-5 py-3 font-semibold">Nombre</th>
                            <th class="w-[16%] px-5 py-3 font-semibold">Tipo</th>
                            <th class="w-[18%] px-5 py-3 font-semibold">Propietario</th>
                            <th class="w-[14%] px-5 py-3 font-semibold">{{ $section === 'trash' ? 'Eliminado' : 'Actualizado' }}</th>
                            <th class="w-[18%] px-5 py-3 text-right font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($items as $item)
                            <tr class="text-[13px] transition hover:bg-warm">
                                <td class="px-5 py-3">
                                    @if ($item['url'])
                                        <a href="{{ $item['url'] }}" class="flex min-w-0 items-center gap-3 font-medium hover:text-brand dark:hover:text-white">
                                            <x-ui.icon :name="$item['icon']" :size="21" alt="" />
                                            <span class="truncate">{{ $item['name'] }}</span>
                                        </a>
                                    @else
                                        <span class="flex min-w-0 items-center gap-3">
                                            <x-ui.icon :name="$item['icon']" :size="21" alt="" />
                                            <span class="truncate font-medium">{{ $item['name'] }}</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-muted">
                                    <span class="block">{{ $item['type'] === 'folder' ? 'Carpeta' : ($item['size'] ?? 'Archivo') }}</span>
                                    @if (isset($item['visibility_label']))
                                        <span class="mt-1 inline-flex rounded-full bg-brand/5 px-2 py-0.5 text-[11px] font-semibold text-brand dark:text-white">
                                            {{ $item['visibility_label'] }}
                                        </span>
                                        <span class="mt-1 block text-[11px]">{{ $item['sharing_label'] }}</span>
                                    @endif
                                </td>
                                <td class="truncate px-5 py-3 text-muted">{{ $item['owner'] ?: 'Sin propietario' }}</td>
                                <td class="px-5 py-3 text-muted">
                                    <span class="block">{{ $item['date'] ?: 'Sin fecha' }}</span>
                                    @if ($item['purge_label'])
                                        <span class="mt-1 block text-[11px] font-semibold text-red-700" title="{{ $item['purge_at'] }}">
                                            {{ $item['purge_label'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    @if ($item['can_download'] || $item['can_rename'] || $item['can_move'] || $item['can_delete'] || $item['can_change_visibility'] || $item['can_restore'] || $item['can_force_delete'])
                                        <span class="flex justify-end gap-1">
                                            @if ($item['can_download'])
                                                <a href="{{ $item['download_url'] }}" class="flex size-8 items-center justify-center rounded-full hover:bg-soft" aria-label="Descargar {{ $item['name'] }}">
                                                    <x-ui.icon name="arrow-down" :size="16" alt="" />
                                                </a>
                                            @endif
                                            @if ($item['can_rename'])
                                                <button type="button" data-modal-open="{{ $item['rename_modal_id'] }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand hover:bg-brand/10 dark:text-white">
                                                    Renombrar
                                                </button>
                                            @endif
                                            @if ($item['can_move'])
                                                <button type="button" data-modal-open="{{ $item['move_modal_id'] }}" class="flex size-8 items-center justify-center rounded-full hover:bg-soft" aria-label="Mover {{ $item['name'] }}">
                                                    <x-ui.icon name="arrow-left-right" :size="16" alt="" />
                                                </button>
                                            @endif
                                            @if ($item['can_delete'])
                                                <button type="button" data-modal-open="{{ $item['delete_modal_id'] }}" class="flex size-8 items-center justify-center rounded-full hover:bg-red-50" aria-label="Eliminar {{ $item['name'] }}">
                                                    <x-ui.icon name="trash" :size="16" alt="" />
                                                </button>
                                            @endif
                                            @if ($item['can_change_visibility'])
                                                <button type="button" data-modal-open="{{ $item['visibility_modal_id'] }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand hover:bg-brand/10 dark:text-white">
                                                    Clasificar
                                                </button>
                                            @endif
                                            @if ($item['can_restore'])
                                                <button type="button" data-modal-open="{{ $item['restore_modal_id'] }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand hover:bg-brand/10 dark:text-white">
                                                    Restaurar
                                                </button>
                                            @endif
                                            @if ($item['can_force_delete'])
                                                <form action="{{ $item['force_delete_url'] }}" method="POST" data-permanent-delete-form data-file-name="{{ $item['name'] }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">
                                                        Eliminar permanentemente
                                                    </button>
                                                </form>
                                            @endif
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($pagination->hasPages())
        <nav class="mt-5" aria-label="Paginación del explorador">
            {{ $pagination->onEachSide(1)->links() }}
        </nav>
    @endif
</x-layouts.app>
