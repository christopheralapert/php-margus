<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeafletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['bail', 'nullable', 'string', 'max:40', "regex:/\\A[\\p{L} '\\-]+\\z/u"],
            'date' => ['bail', 'nullable', 'date_format:Y-m-d', 'after_or_equal:'.today()->subYear()->format('Y-m-d'), 'before_or_equal:'.today()->addDay()->format('Y-m-d')],
            'consent' => [Rule::excludeIf(! $this->filled('first_name') && ! $this->filled('date')), 'required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.string' => __('wizard.validation.name'),
            'first_name.max' => __('wizard.validation.name_max'),
            'first_name.regex' => __('wizard.validation.name'),
            'date.date_format' => __('wizard.validation.date'),
            'date.after_or_equal' => __('wizard.validation.date_range'),
            'date.before_or_equal' => __('wizard.validation.date_range'),
            'consent.required' => __('wizard.validation.consent'),
            'consent.accepted' => __('wizard.validation.consent'),
        ];
    }
}
