<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWizardProceduresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'procedures' => ['required', 'array', 'min:1', 'max:5'],
            'procedures.*' => ['bail', 'required', 'integer', 'distinct', Rule::exists('procedures', 'id')->where('is_active', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'procedures.required' => __('wizard.validation.required'),
            'procedures.array' => __('wizard.validation.invalid'),
            'procedures.min' => __('wizard.validation.required'),
            'procedures.max' => __('wizard.validation.max'),
            'procedures.*.required' => __('wizard.validation.invalid'),
            'procedures.*.integer' => __('wizard.validation.invalid'),
            'procedures.*.distinct' => __('wizard.validation.invalid'),
            'procedures.*.exists' => __('wizard.validation.invalid'),
        ];
    }
}
