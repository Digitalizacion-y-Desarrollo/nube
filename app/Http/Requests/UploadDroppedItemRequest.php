<?php

namespace App\Http\Requests;

use App\Enums\FileVisibility;
use App\Http\Requests\Concerns\ValidatesCollaborators;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UploadDroppedItemRequest extends FormRequest
{
    use ValidatesCollaborators;

    public function authorize(): bool
    {
        $folderId = $this->input('folder_id');
        $folder = is_string($folderId) && Str::isUuid($folderId)
            ? Folder::query()->find($folderId)
            : null;
        $visibility = FileVisibility::tryFrom((string) $this->input('visibility'));

        if ($visibility === null) {
            return true;
        }

        if ($this->input('kind') === 'directory') {
            return $this->user()?->can('create', [Folder::class, $folder, $visibility]) ?? false;
        }

        return $this->user()?->can('upload', [File::class, $folder, $visibility]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $extensions = implode(',', config('nube.files.extensions'));
        $mimeTypes = implode(',', config('nube.files.mime_types'));

        return [
            'kind' => ['required', Rule::in(['file', 'directory'])],
            'relative_path' => [
                'required',
                'string',
                'max:2000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! $this->isSafeRelativePath($value)) {
                        $fail('La ruta relativa del elemento no es válida.');
                    }
                },
            ],
            'file' => [
                Rule::requiredIf($this->input('kind') === 'file'),
                Rule::prohibitedIf($this->input('kind') === 'directory'),
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
            'kind.required' => 'No se indicó el tipo de elemento.',
            'kind.in' => 'El tipo de elemento no es válido.',
            'relative_path.required' => 'No se recibió la ruta relativa del elemento.',
            'relative_path.max' => 'La ruta del elemento es demasiado larga.',
            'file.required' => 'No se recibió el archivo.',
            'file.prohibited' => 'Una carpeta no debe incluir contenido de archivo.',
            'file.file' => 'El elemento seleccionado no es un archivo válido.',
            'file.max' => 'El archivo no puede exceder 200 MB.',
            'file.extensions' => 'La extensión del archivo no está permitida.',
            'file.mimetypes' => 'El tipo MIME del archivo no está permitido.',
            'folder_id.uuid' => 'La carpeta de destino no es válida.',
            'folder_id.exists' => 'La carpeta de destino no existe o fue eliminada.',
            'visibility.required' => 'Selecciona la clasificación del contenido.',
            'visibility.enum' => 'La clasificación seleccionada no es válida.',
            ...$this->collaborationMessages(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $path = $this->input('relative_path');

        if (is_string($path)) {
            $this->merge([
                'relative_path' => str_replace('\\', '/', $path),
            ]);
        }

        if ($this->input('folder_id') === '') {
            $this->merge(['folder_id' => null]);
        }

        if (! $this->has('visibility')) {
            $this->merge(['visibility' => FileVisibility::Private->value]);
        }

        $this->prepareCollaborationForValidation();
    }

    private function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || str_starts_with($path, '/') || preg_match('/^[a-z]:\//i', $path)) {
            return false;
        }

        $segments = explode('/', $path);

        if (count($segments) > 101) {
            return false;
        }

        foreach ($segments as $index => $segment) {
            $isFileName = $this->input('kind') === 'file' && $index === array_key_last($segments);
            $maxLength = $isFileName ? 255 : 150;

            if ($segment === ''
                || in_array($segment, ['.', '..'], true)
                || mb_strlen($segment) > $maxLength
                || preg_match('/[\x00-\x1F\x7F]/u', $segment)) {
                return false;
            }
        }

        return true;
    }
}
