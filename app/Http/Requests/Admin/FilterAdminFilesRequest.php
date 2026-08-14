<?php

namespace App\Http\Requests\Admin;

use App\Enums\FileVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterAdminFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'visibility' => ['nullable', Rule::enum(FileVisibility::class)],
            'type' => ['nullable', 'string', 'max:20', 'regex:/^[a-z0-9]+$/i'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::in(['all', 'active', 'trashed'])],
            'per_page' => ['nullable', Rule::in([10, 20, 50])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => $this->filled('q') ? trim((string) $this->input('q')) : null,
            'type' => $this->filled('type')
                ? strtolower(ltrim(trim((string) $this->input('type')), '.'))
                : null,
        ]);
    }
}
