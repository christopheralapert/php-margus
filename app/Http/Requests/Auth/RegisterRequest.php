<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:40', "regex:/\A[\p{L}'\-]+\z/u"],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'consent' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => __('account.name_invalid'),
            'name.max' => __('account.name_invalid'),
            'consent.required' => __('account.consent_required'),
            'consent.accepted' => __('account.consent_required'),
        ];
    }
}
