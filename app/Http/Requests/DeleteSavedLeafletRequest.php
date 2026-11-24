<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteSavedLeafletRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('delete', $this->route('leaflet'));

        return true;
    }

    public function rules(): array
    {
        return ['confirm' => ['required', 'accepted']];
    }

    public function messages(): array
    {
        return [
            'confirm.required' => __('account.confirm_required'),
            'confirm.accepted' => __('account.confirm_required'),
        ];
    }
}
