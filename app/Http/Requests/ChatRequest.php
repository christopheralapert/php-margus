<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

final class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless(config('chat.enabled'), 404);

        return true;
    }

    public function rules(): array
    {
        return [
            'procedures' => ['required', 'array', 'list', 'min:1', 'max:5'],
            'procedures.*' => ['bail', 'required', 'string', 'distinct', Rule::exists('procedures', 'slug')->where('is_active', true)],
            'message' => ['required', 'string', 'max:'.config('chat.max_chars')],
            'history' => ['sometimes', 'array', 'list', 'max:6'],
            'history.*' => ['required', 'array:role,content'],
            'history.*.role' => ['required', Rule::in(['user', 'assistant'])],
            'history.*.content' => ['required', 'string', 'max:'.config('chat.max_chars')],
        ];
    }

    public function messages(): array
    {
        return collect(['required', 'array', 'list', 'min', 'max', 'string', 'distinct', 'exists', 'in'])
            ->mapWithKeys(fn ($rule) => ['*.'.$rule => __('chat.validation')])->all();
    }

    protected function failedValidation(Validator $validator): void
    {
        // Always JSON; never flash chat input into the session on validation failure.
        throw new HttpResponseException(response()->json([
            'message' => __('chat.validation'),
            'errors' => $validator->errors(),
        ], 422));
    }
}
