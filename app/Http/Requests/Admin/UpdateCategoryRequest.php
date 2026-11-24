<?php

namespace App\Http\Requests\Admin;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'icon' => ['nullable', 'file', 'extensions:svg', 'max:50'],
        ]);
    }
}
