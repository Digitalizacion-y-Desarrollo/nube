<?php

namespace App\Http\Requests;

use App\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class MoveFolderRequest extends FormRequest
{
    protected $errorBag = 'moveFolder';

    public function authorize(): bool
    {
        $folder = $this->route('folder');

        if (! $folder instanceof Folder) {
            return false;
        }

        $destinationId = $this->input('destination_folder_id');

        if ($destinationId === null || $destinationId === '') {
            return $this->user()?->can('move', [$folder, null]) ?? false;
        }

        if (! is_string($destinationId) || ! Str::isUuid($destinationId)) {
            return true;
        }

        $destination = Folder::query()->find($destinationId);

        return $destination !== null
            && ($this->user()?->can('move', [$folder, $destination]) ?? false);
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
                    /** @var Folder $folder */
                    $folder = $this->route('folder');

                    if ($value === $folder->parent_id
                        || ($value === null && $folder->parent_id === null)) {
                        $fail('Selecciona una ubicación diferente.');

                        return;
                    }

                    if (! is_string($value)) {
                        return;
                    }

                    $destination = Folder::query()->find($value);

                    while ($destination !== null) {
                        if ($destination->id === $folder->id) {
                            $fail('No puedes mover una carpeta dentro de sí misma o de una subcarpeta.');

                            return;
                        }

                        $destination = $destination->parent()->first();
                    }

                    $duplicate = Folder::query()
                        ->where('owner_id', $folder->owner_id)
                        ->whereKeyNot($folder->id)
                        ->whereRaw('LOWER(name) = ?', [Str::lower($folder->name)])
                        ->when(
                            is_string($value),
                            fn ($folders) => $folders->where('parent_id', $value),
                            fn ($folders) => $folders->whereNull('parent_id'),
                        )
                        ->exists();

                    if ($duplicate) {
                        $fail('Ya existe una carpeta con ese nombre en la ubicación seleccionada.');
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
