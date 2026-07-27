<?php

namespace App\Http\Requests;

use App\Enums\FileVisibility;
use App\Http\Requests\Concerns\ValidatesCollaborators;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UploadFileRequest extends FormRequest
{
    use ValidatesCollaborators;

    protected $errorBag = 'uploadFile';

    public function authorize(): bool
    {
        $folderId = $this->input('folder_id');
        $visibility = is_string($this->input('visibility'))
            ? FileVisibility::tryFrom($this->input('visibility'))
            : null;

        if ($visibility === null) {
            return true;
        }

        if ($folderId === null || $folderId === '') {
            return $this->user()?->can('upload', [File::class, null, $visibility]) ?? false;
        }

        if (! is_string($folderId) || ! Str::isUuid($folderId)) {
            return true;
        }

        $folder = Folder::query()->find($folderId);

        return $folder !== null
            && ($this->user()?->can('upload', [File::class, $folder, $visibility]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $extensions = implode(',', config('nube.files.extensions'));
        $mimeTypes = implode(',', config('nube.files.mime_types'));

        return [
            'file' => [
                'bail',
                'required',
                'file',
                'max:'.config('nube.files.max_size_kb'),
                "extensions:{$extensions}",
                "mimetypes:{$mimeTypes}",
            ],
            'folder_id' => ['nullable', 'uuid', 'exists:folders,id'],
            'visibility' => ['required', Rule::enum(FileVisibility::class)],
            ...$this->collaborationRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Selecciona un archivo.',
            'file.file' => 'El elemento seleccionado no es un archivo válido.',
            'file.max' => 'El archivo no puede exceder 200 MB.',
            'file.extensions' => 'La extensión del archivo no está permitida.',
            'file.mimetypes' => 'El tipo MIME del archivo no está permitido.',
            'folder_id.uuid' => 'La carpeta de destino no es válida.',
            'folder_id.exists' => 'La carpeta de destino no existe o fue eliminada.',
            'visibility.required' => 'Selecciona la clasificación del archivo.',
            'visibility.enum' => 'La clasificación seleccionada no es válida.',
            ...$this->collaborationMessages(),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('folder_id') === '') {
            $this->merge(['folder_id' => null]);
        }

        if (! $this->has('visibility')) {
            $this->merge(['visibility' => FileVisibility::Private->value]);
        }

        $this->prepareCollaborationForValidation();
    }
}
