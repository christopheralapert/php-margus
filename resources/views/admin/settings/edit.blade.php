@extends('layouts.admin')
@section('title', __('settings.title'))
@section('content')
    <h1 class="text-3xl font-bold tracking-tight">{{ __('settings.title') }}</h1>
    <p class="mt-3 max-w-2xl text-slate-600">{{ __('settings.intro') }}</p>
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data"
          x-data="{ language: @js($errors->any() ? null : app()->getLocale()), names: @js(old('clinic.name', $values['clinic']['name'])), phone: @js(old('clinic.phone', $values['clinic']['phone'])) }"
          @invalid.capture="language = null"
          class="mt-8 max-w-4xl space-y-10 rounded-xl border border-slate-200 bg-white p-5 sm:p-8">
        @csrf
        @method('PUT')
        @if ($errors->any())
            <ul class="list-inside list-disc text-sm text-red-700" role="alert">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        @endif
        <fieldset>
            <legend class="text-xl font-bold">{{ __('settings.clinic') }}</legend>
            <div role="tablist" aria-label="{{ __('settings.translations') }}" class="mt-5 flex gap-2" style="display: none" x-show="true">
                @foreach (['et', 'en'] as $locale)
                    <button id="settings_tab_{{ $locale }}" type="button" role="tab" @click="language = '{{ $locale }}'"
                            @keydown.arrow-right.prevent="language = '{{ $locale === 'et' ? 'en' : 'et' }}'; $el.parentElement.querySelector('#settings_tab_' + language).focus()"
                            @keydown.arrow-left.prevent="language = '{{ $locale === 'et' ? 'en' : 'et' }}'; $el.parentElement.querySelector('#settings_tab_' + language).focus()"
                            :aria-selected="language === '{{ $locale }}'" :tabindex="language === '{{ $locale }}' || language === null ? 0 : -1"
                            aria-controls="clinic_{{ $locale }} disclaimer_{{ $locale }}"
                            :class="language === '{{ $locale }}' ? 'border-brand-primary text-brand-primary' : 'border-slate-300'"
                            class="min-h-11 rounded-md border px-4 text-sm font-semibold hover:bg-brand-soft focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('settings.languages.'.$locale) }}</button>
                @endforeach
            </div>
            @foreach (['et', 'en'] as $locale)
                <div id="clinic_{{ $locale }}" role="tabpanel" aria-labelledby="settings_tab_{{ $locale }}" x-show="language === null || language === '{{ $locale }}'" class="mt-6 grid gap-6 sm:grid-cols-2">
                    @include('admin.settings._field', ['key' => 'clinic.name.'.$locale, 'label' => __('settings.fields.name').' ('.__('settings.languages.'.$locale).')', 'lang' => $locale, 'max' => 120, 'model' => 'names.'.$locale])
                    @include('admin.settings._field', ['key' => 'clinic.address.'.$locale, 'label' => __('settings.fields.address').' ('.__('settings.languages.'.$locale).')', 'lang' => $locale, 'max' => 300])
                    @include('admin.settings._field', ['key' => 'clinic.hours.'.$locale, 'label' => __('settings.fields.hours').' ('.__('settings.languages.'.$locale).')', 'lang' => $locale, 'max' => 300])
                </div>
            @endforeach
            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                @include('admin.settings._field', ['key' => 'clinic.phone', 'label' => __('settings.fields.phone'), 'type' => 'tel', 'max' => 20, 'pattern' => '[+0-9 ]{6,20}', 'model' => 'phone', 'help' => __('settings.phone_help')])
                @include('admin.settings._field', ['key' => 'clinic.after_hours_phone', 'label' => __('settings.fields.after_hours_phone'), 'type' => 'tel', 'max' => 20, 'pattern' => '[+0-9 ]{6,20}', 'help' => __('settings.phone_help')])
                @include('admin.settings._field', ['key' => 'clinic.emergency', 'label' => __('settings.fields.emergency'), 'max' => 4, 'pattern' => '[0-9]{3,4}'])
                @include('admin.settings._field', ['key' => 'clinic.email', 'label' => __('settings.fields.email'), 'type' => 'email', 'max' => 254])
                @include('admin.settings._field', ['key' => 'clinic.website', 'label' => __('settings.fields.website'), 'type' => 'url', 'max' => 2048, 'pattern' => 'https://.*', 'help' => __('settings.website_help')])
            </div>
            <div class="mt-6 border-t border-slate-200 pt-5">
                <p class="text-sm text-slate-600">{{ __('settings.preview') }}</p>
                <p class="mt-2 font-semibold" x-text="(names[language || @js(app()->getLocale())] || '') + ' · ' + phone">{{ old('clinic.name.'.app()->getLocale(), $values['clinic']['name'][app()->getLocale()]) }} · {{ old('clinic.phone', $values['clinic']['phone']) }}</p>
            </div>
        </fieldset>
        <fieldset class="border-t border-slate-200 pt-6">
            <legend class="text-xl font-bold">{{ __('settings.brand') }}</legend>
            @if ($warnings)
                <aside role="status" class="mb-6 rounded-lg bg-amber-50 p-4 text-amber-900">
                    <h2 class="font-semibold">{{ __('settings.contrast_title') }}</h2>
                    <p class="mt-1 text-sm">{{ __('settings.contrast_help') }}</p>
                    <ul class="mt-2 list-inside list-disc text-sm">@foreach ($warnings as $warning) <li>{{ $warning }}</li> @endforeach</ul>
                </aside>
            @endif
            <div class="grid gap-6 sm:grid-cols-2">
                @foreach ($values['brand']['colors'] as $color => $hex)
                    <div x-data="{ color: @js(old('brand.colors.'.$color, $hex)) }">
                        <label for="color_{{ $color }}" class="block text-sm font-semibold">{{ __('settings.fields.'.$color) }}</label>
                        <div class="mt-2 flex gap-3">
                            <input type="color" value="{{ $hex }}" :value="/^#[0-9a-f]{6}$/i.test(color) ? color : @js($hex)" @input="color = $event.target.value.toUpperCase()" aria-label="{{ __('settings.color_picker', ['color' => __('settings.fields.'.$color)]) }}" class="h-11 w-14 shrink-0 rounded-md border-slate-300 p-1 focus:ring-brand-primary">
                            <input id="color_{{ $color }}" name="brand[colors][{{ $color }}]" value="{{ old('brand.colors.'.$color, $hex) }}" x-model="color" required pattern="#[0-9A-Fa-f]{6}" maxlength="7" aria-invalid="{{ $errors->has('brand.colors.'.$color) ? 'true' : 'false' }}" aria-describedby="color_{{ $color }}_error" class="min-w-0 flex-1 rounded-md border-slate-300 focus:border-brand-primary focus:ring-brand-primary">
                        </div>
                        <div id="color_{{ $color }}_error"><x-input-error :messages="$errors->get('brand.colors.'.$color)" class="mt-2" /></div>
                    </div>
                @endforeach
            </div>
        </fieldset>
        <fieldset class="border-t border-slate-200 pt-6">
            <legend class="text-xl font-bold">{{ __('settings.leaflet') }}</legend>
            @foreach (['et', 'en'] as $locale)
                <div id="disclaimer_{{ $locale }}" role="tabpanel" aria-labelledby="settings_tab_{{ $locale }}" x-show="language === null || language === '{{ $locale }}'" class="mt-4">
                    @include('admin.settings._field', ['key' => 'leaflet.disclaimer.'.$locale, 'label' => __('settings.fields.disclaimer').' ('.__('settings.languages.'.$locale).')', 'type' => 'textarea', 'lang' => $locale, 'max' => 300])
                </div>
            @endforeach
        </fieldset>
        <fieldset class="border-t border-slate-200 pt-6">
            <legend class="text-xl font-bold">{{ __('settings.retention') }}</legend>
            <p class="mb-5 text-sm text-slate-600">{{ __('settings.retention_help') }}</p>
            <div class="grid gap-6 sm:grid-cols-2">
                @include('admin.settings._field', ['key' => 'retention.saved_leaflet_months', 'label' => __('settings.fields.saved_leaflet_months'), 'type' => 'number', 'min' => 1, 'limit' => 60])
                @include('admin.settings._field', ['key' => 'retention.unverified_account_days', 'label' => __('settings.fields.unverified_account_days'), 'type' => 'number', 'min' => 1, 'limit' => 365])
            </div>
        </fieldset>
        <fieldset class="border-t border-slate-200 pt-6">
            <legend class="text-xl font-bold">{{ __('settings.logo') }}</legend>
            <img src="{{ asset('brand/logo.svg') }}?v={{ file_exists(public_path('brand/logo.svg')) ? filemtime(public_path('brand/logo.svg')) : 0 }}" alt="{{ __('settings.logo_alt') }}" class="mb-5 h-20 max-w-full object-contain">
            <label for="logo" class="block text-sm font-semibold">{{ __('settings.fields.logo') }}</label>
            <input id="logo" name="logo" type="file" accept=".svg,image/svg+xml" aria-invalid="{{ $errors->has('logo') ? 'true' : 'false' }}" aria-describedby="logo_help logo_error" class="mt-2 block w-full rounded-md text-sm file:mr-4 file:min-h-11 file:rounded-md file:border-0 file:bg-brand-soft file:px-4 file:font-semibold file:text-brand-ink focus-visible:ring-2 focus-visible:ring-brand-primary">
            <p id="logo_help" class="mt-2 text-sm text-slate-600">{{ __('settings.logo_help') }}</p>
            <div id="logo_error"><x-input-error :messages="$errors->get('logo')" class="mt-2" /></div>
        </fieldset>
        <div class="border-t border-slate-200 pt-6">
            <button class="min-h-11 rounded-md bg-brand-primary px-5 font-semibold text-white hover:bg-blue-700 focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">{{ __('settings.save') }}</button>
        </div>
    </form>
@endsection
