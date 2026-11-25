@php($inputId = str_replace(['.', '*'], '_', $field))
<div x-data="{ tab: @js($errors->any() ? null : app()->getLocale()) }" class="space-y-3">
    <p class="text-sm font-semibold">{{ $label }}</p>
    <div class="flex gap-2" x-show="true" style="display: none">
        @foreach (['et', 'en'] as $language)
            <button type="button" @click="tab = '{{ $language }}'" :aria-pressed="tab === '{{ $language }}'" :class="tab === '{{ $language }}' ? 'bg-brand-primary text-white' : 'bg-slate-100 text-slate-700'" class="min-h-11 rounded-md px-4 text-sm font-semibold focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('admin.procedure.'.$language) }}</button>
        @endforeach
    </div>
    @foreach (['et', 'en'] as $language)
        @php($key = $field.'.'.$language)
        @php($htmlName = preg_replace('/\.([^.]+)/', '[$1]', $key))
        <div x-show="tab === null || tab === '{{ $language }}'">
            <label for="{{ $inputId }}_{{ $language }}" class="block text-sm text-slate-600">{{ $label }} — {{ __('admin.procedure.'.$language) }}</label>
            <textarea id="{{ $inputId }}_{{ $language }}" name="{{ $htmlName }}" lang="{{ $language }}" rows="{{ $rows ?? 3 }}" maxlength="10000" aria-invalid="{{ $errors->has($key) ? 'true' : 'false' }}" aria-describedby="{{ $inputId }}_{{ $language }}_error" class="mt-2 w-full rounded-md border-slate-300 focus:border-brand-primary focus:ring-brand-primary">{{ old($key, $translations[$language] ?? '') }}</textarea>
            <div id="{{ $inputId }}_{{ $language }}_error"><x-input-error :messages="$errors->get($key)" /></div>
        </div>
    @endforeach
</div>
