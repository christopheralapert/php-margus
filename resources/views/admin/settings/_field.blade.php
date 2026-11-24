@php
    $id = str_replace('.', '_', $key);
    $parts = explode('.', $key);
    $name = array_shift($parts).implode('', array_map(fn ($part) => '['.$part.']', $parts));
    $value = old($key, data_get($values, $key));
@endphp
<div>
    <label for="{{ $id }}" class="block text-sm font-semibold">{{ $label }}</label>
    @if (($type ?? 'text') === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="3" required maxlength="{{ $max ?? 300 }}" @if (isset($lang)) lang="{{ $lang }}" @endif
                  aria-invalid="{{ $errors->has($key) ? 'true' : 'false' }}" aria-describedby="{{ $id }}_error"
                  class="mt-2 w-full rounded-md border-slate-300 focus:border-brand-primary focus:ring-brand-primary">{{ $value }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type ?? 'text' }}" value="{{ $value }}" @required($key !== 'clinic.website')
               @if (isset($lang)) lang="{{ $lang }}" @endif
               @if (isset($max)) maxlength="{{ $max }}" @endif
               @if (isset($min)) min="{{ $min }}" max="{{ $limit }}" step="1" @endif
               @if (isset($pattern)) pattern="{{ $pattern }}" @endif
               @if (isset($model)) x-model="{{ $model }}" @endif
               aria-invalid="{{ $errors->has($key) ? 'true' : 'false' }}" aria-describedby="{{ $id }}_error{{ isset($help) ? ' '.$id.'_help' : '' }}"
               class="mt-2 w-full rounded-md border-slate-300 focus:border-brand-primary focus:ring-brand-primary">
    @endif
    @if (isset($help)) <p id="{{ $id }}_help" class="mt-2 text-sm text-slate-600">{{ $help }}</p> @endif
    <div id="{{ $id }}_error"><x-input-error :messages="$errors->get($key)" class="mt-2" /></div>
</div>
