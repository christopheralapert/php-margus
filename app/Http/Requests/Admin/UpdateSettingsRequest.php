<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'clinic' => ['required', 'array:name,phone,after_hours_phone,emergency,email,address,hours,website'],
            'clinic.phone' => ['required', 'string', 'min:6', 'max:20', 'regex:/\A[+0-9 ]+\z/'],
            'clinic.after_hours_phone' => ['required', 'string', 'min:6', 'max:20', 'regex:/\A[+0-9 ]+\z/'],
            'clinic.emergency' => ['required', 'string', 'regex:/\A[0-9]{3,4}\z/'],
            'clinic.email' => ['required', 'string', 'email', 'max:254'],
            'clinic.website' => ['nullable', 'string', 'url:https', 'max:2048'],
            'brand' => ['required', 'array:colors'],
            'brand.colors' => ['required', 'array:primary,secondary,accent,danger,ink,paper,soft'],
            'leaflet' => ['required', 'array:disclaimer'],
            'retention' => ['required', 'array:saved_leaflet_months,unverified_account_days'],
            'retention.saved_leaflet_months' => ['required', 'integer', 'between:1,60'],
            'retention.unverified_account_days' => ['required', 'integer', 'between:1,365'],
            'logo' => ['nullable', 'file', 'extensions:svg', 'max:200'],
        ];

        foreach (['clinic.name' => 120, 'clinic.address' => 300, 'clinic.hours' => 300, 'leaflet.disclaimer' => 300] as $key => $max) {
            $rules[$key] = ['required', 'array:et,en'];
            foreach (['et', 'en'] as $locale) {
                $rules[$key.'.'.$locale] = ['required', 'string', 'max:'.$max];
            }
        }
        foreach (['primary', 'secondary', 'accent', 'danger', 'ink', 'paper', 'soft'] as $color) {
            $rules['brand.colors.'.$color] = ['required', 'string', 'regex:/\A#[0-9A-Fa-f]{6}\z/'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach (array_keys($this->rules()) as $key) {
            $parts = explode('.', $key);
            $locale = in_array(end($parts), ['et', 'en'], true) ? array_pop($parts) : null;
            $attributes[$key] = __('settings.fields.'.end($parts)).($locale ? ' ('.__('settings.languages.'.$locale).')' : '');
        }

        return $attributes;
    }
}
