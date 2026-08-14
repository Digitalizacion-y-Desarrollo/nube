<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'file',
                'image',
                Rule::file()
                    ->extensions((array) config('nube.avatars.extensions', ['jpg', 'jpeg', 'png']))
                    ->max((int) config('nube.avatars.max_size_kb', 10240)),
                'mimetypes:'.implode(',', (array) config('nube.avatars.mime_types', ['image/jpeg', 'image/png'])),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxMegabytes = round(((int) config('nube.avatars.max_size_kb', 10240)) / 1024, 1);

        return [
            'avatar.required' => 'Selecciona una imagen para tu foto de perfil.',
            'avatar.image' => 'El archivo debe ser una imagen.',
            'avatar.extensions' => 'La foto debe ser JPG o PNG.',
            'avatar.mimetypes' => 'La foto debe ser JPG o PNG.',
            'avatar.max' => "La foto no puede superar {$maxMegabytes} MB.",
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['avatar' => 'foto de perfil'];
    }
}
