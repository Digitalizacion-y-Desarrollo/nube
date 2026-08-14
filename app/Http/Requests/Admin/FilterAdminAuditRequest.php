<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterAdminAuditRequest extends FormRequest
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
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'action' => ['nullable', 'string', 'max:100'],
            'resource_type' => ['nullable', 'string', 'max:100'],
            'ip' => ['nullable', 'string', 'max:45'],
            'scope' => ['nullable', Rule::in(['all', 'administrative', 'user'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', Rule::in([25, 50, 100])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => $this->filled('q') ? trim((string) $this->input('q')) : null,
            'action' => $this->filled('action') ? trim((string) $this->input('action')) : null,
            'ip' => $this->filled('ip') ? trim((string) $this->input('ip')) : null,
        ]);
    }
}
