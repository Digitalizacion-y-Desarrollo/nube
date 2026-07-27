<?php

namespace App\Http\Requests;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class MoveFileRequest extends FormRequest
{
    protected $errorBag = 'moveFile';

    public function authorize(): bool
    {
        $file = $this->route('file');

        if (! $file instanceof File) {
            return false;
        }

        $folderId = $this->input('destination_folder_id');

        if ($folderId === null || $folderId === '') {
            return $this->user()?->can('move', [$file, null]) ?? false;
        }

        if (! is_string($folderId) || ! Str::isUuid($folderId)) {
            return true;
        }

        $destination = Folder::query()->find($folderId);

        return $destination !== null
            && ($this->user()?->can('move', [$file, $destination]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'destination_folder_id' => [
                'nullable',
                'uuid',
                'exists:folders,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    /** @var File $file */
                    $file = $this->route('file');

                    if ($value === $file->folder_id || ($value === null && $file->folder_id === null)) {
                        $fail('Selecciona una ubicación diferente.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'destination_folder_id.uuid' => 'La carpeta de destino no es válida.',
            'destination_folder_id.exists' => 'La carpeta de destino no existe o fue eliminada.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('destination_folder_id') === '') {
            $this->merge(['destination_folder_id' => null]);
        }
    }
}
