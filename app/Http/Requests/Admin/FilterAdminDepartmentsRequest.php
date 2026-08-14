<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterAdminDepartmentsRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
            'per_page' => ['nullable', Rule::in([10, 20, 50])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => $this->filled('q') ? trim((string) $this->input('q')) : null,
        ]);
    }
}
