<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->input('is_active', false)]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'array:et,en'],
            'name.et' => ['required', 'string', 'max:80'],
            'name.en' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('categories', 'slug')->ignore($this->route('category'))],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/D'],
            'sort' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'is_active' => ['required', 'boolean'],
            'icon' => ['required', 'file', 'extensions:svg', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return collect(['name', 'slug', 'color', 'sort', 'is_active', 'icon'])
            ->mapWithKeys(fn (string $key) => [$key => __('admin.fields.'.$key)])
            ->merge(['name.et' => __('admin.fields.name_et'), 'name.en' => __('admin.fields.name_en')])->all();
    }
}
