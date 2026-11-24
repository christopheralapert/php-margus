<?php

namespace App\Http\Requests\Admin;

use App\Enums\AdviceSection;
use App\Enums\ProcedureStatus;
use App\Enums\TimelinePhase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProcedureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->input('is_active', false), 'advice_items' => $this->input('advice_items', [])]);
    }

    public function rules(): array
    {
        $phases = implode(',', array_column(TimelinePhase::cases(), 'value'));
        $sections = implode(',', array_column(AdviceSection::cases(), 'value'));
        $rules = [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('procedures', 'slug')->ignore($this->route('procedure'))],
            'sort' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'is_active' => ['required', 'boolean'],
            'color' => ['nullable', 'string', 'regex:/^#[a-fA-F0-9]{6}$/D'],
            'status' => ['required', Rule::enum(ProcedureStatus::class)],
            'reviewed_by' => ['nullable', 'string', 'max:255'],
            'timeline_steps' => ['required', 'array:'.$phases, 'size:4'],
            'advice_items' => ['present', 'array:'.$sections],
            'advice_items.*' => ['array', 'max:200'],
            'advice_items.*.*' => ['array:text,merge_key'],
            'advice_items.*.*.text' => ['required', 'array:et,en'],
            'advice_items.*.*.text.et' => ['nullable', 'required_with:advice_items.*.*.text.en', 'string', 'max:10000'],
            'advice_items.*.*.text.en' => ['nullable', 'required_with:advice_items.*.*.text.et', 'string', 'max:10000'],
            'advice_items.*.*.merge_key' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9_.]+$/D'],
            'illustration' => ['nullable', 'file', 'extensions:svg,png', 'max:2048'],
        ];
        foreach (['name', 'summary', 'search_keywords', 'follow_up', 'review_note'] as $field) {
            $rules[$field] = ['required', 'array:et,en'];
            foreach (['et', 'en'] as $locale) {
                $rules[$field.'.'.$locale] = [$field === 'review_note' ? 'nullable' : 'required', 'string', 'max:10000'];
            }
        }
        foreach (TimelinePhase::cases() as $phase) {
            $prefix = 'timeline_steps.'.$phase->value;
            $rules[$prefix] = ['required', 'array:marker,title,body'];
            foreach (['marker', 'title', 'body'] as $field) {
                $rules[$prefix.'.'.$field] = ['required', 'array:et,en'];
                foreach (['et', 'en'] as $locale) {
                    $rules[$prefix.'.'.$field.'.'.$locale] = ['required', 'string', 'max:10000'];
                }
            }
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $dose = '/\d+([.,]\d+)?\s?(mg|ml|g|µg|mcg|tabl|tablett|tablet|tablets|kapsel|capsule)\b|\d+([.,]\d+)?\s*(korda\s+päevas|times\s+a\s+day|x\s+daily)\b/iu';
            foreach (['timeline_steps', 'advice_items'] as $group) {
                $input = $this->input($group, []);
                if (! is_array($input)) {
                    continue;
                }
                foreach (Arr::dot($input) as $key => $text) {
                    if (is_string($text) && ! str_ends_with($key, 'merge_key') && preg_match($dose, $text)) {
                        $validator->errors()->add($group.'.'.$key, __('admin.procedure.dose'));
                    }
                }
            }
            $upload = $this->file('illustration');
            if ($upload instanceof UploadedFile && $upload->isValid()) {
                if (strtolower($upload->getClientOriginalExtension()) === 'svg') {
                    if ($upload->getSize() > 200 * 1024) {
                        $validator->errors()->add('illustration', __('admin.procedure.svg_invalid'));
                    }
                } else {
                    $size = @getimagesize($upload->getPathname());
                    if (! $size || $size[2] !== IMAGETYPE_PNG) {
                        $validator->errors()->add('illustration', __('admin.procedure.png_invalid'));
                    } elseif ($size[0] < 709) {
                        $validator->errors()->add('illustration', __('admin.procedure.png_small'));
                    }
                }
            }
        }];
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach (array_keys($this->rules()) as $key) {
            $parts = explode('.', $key);
            $field = in_array(end($parts), ['et', 'en']) ? $parts[count($parts) - 2] : end($parts);
            $label = match (true) {
                $field === '*' => __('admin.procedure.advice_items'),
                TimelinePhase::tryFrom($field) !== null => TimelinePhase::from($field)->label(),
                in_array($field, ['slug', 'sort', 'is_active', 'color', 'name']) => __('admin.fields.'.$field),
                default => __('admin.procedure.'.$field),
            };
            $attributes[$key] = $label.(in_array(end($parts), ['et', 'en']) ? ' ('.__('admin.procedure.'.end($parts)).')' : '');
        }

        return $attributes;
    }
}
