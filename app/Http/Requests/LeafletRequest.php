<?php

namespace App\Http\Requests;

use App\Support\DateFormat;
use Carbon\CarbonImmutable;
use Closure;
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
            'date' => ['bail', 'nullable', function (string $attribute, mixed $value, Closure $fail): void {
                $date = DateFormat::parseInput($value);
                if ($date === null) {
                    $fail(__('wizard.validation.date'));
                } elseif ($date->lessThan(today()->subYear()) || $date->greaterThan(today()->addDay())) {
                    $fail(__('wizard.validation.date_range'));
                }
            }],
            'consent' => [Rule::excludeIf(! $this->filled('first_name') && ! $this->filled('date')), 'required', 'accepted'],
        ];
    }

    public function procedureDate(): ?CarbonImmutable
    {
        return DateFormat::parseInput($this->validated('date'));
    }

    public function messages(): array
    {
        return [
            'first_name.string' => __('wizard.validation.name'),
            'first_name.max' => __('wizard.validation.name_max'),
            'first_name.regex' => __('wizard.validation.name'),
            'consent.required' => __('wizard.validation.consent'),
            'consent.accepted' => __('wizard.validation.consent'),
        ];
    }
}
