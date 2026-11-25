<?php

namespace App\Http\Requests;

use App\Enums\LeafletFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeafletFormatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['format' => ['sometimes', 'required', Rule::enum(LeafletFormat::class)]];
    }

    public function selectedFormat(): LeafletFormat
    {
        return LeafletFormat::from($this->validated('format', LeafletFormat::A5->value));
    }
}
