<?php

namespace App\Http\Requests;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class RestoreFileRequest extends FormRequest
{
    protected $errorBag = 'restoreFile';

    public function authorize(): bool
    {
        $file = File::onlyTrashed()->find($this->route('file'));

        if ($file === null) {
            return false;
        }

        $folderId = $this->input('destination_folder_id');

        if ($folderId === null || $folderId === '') {
            return $this->user()?->can('restore', [$file, null]) ?? false;
        }

        if (! is_string($folderId) || ! Str::isUuid($folderId)) {
            return true;
        }

        $destination = Folder::query()->find($folderId);

        return $destination !== null
            && ($this->user()?->can('restore', [$file, $destination]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'destination_folder_id' => ['nullable', 'uuid', 'exists:folders,id'],
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
