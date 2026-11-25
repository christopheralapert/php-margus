<x-input-error :messages="$errors->get('name')" class="mb-4" />
<div class="grid gap-6 sm:grid-cols-2">
    @foreach (['et', 'en'] as $locale)
        <div>
            <label for="name_{{ $locale }}" class="block text-sm font-semibold">{{ __('admin.fields.name_'.$locale) }}</label>
            <input id="name_{{ $locale }}" name="name[{{ $locale }}]" lang="{{ $locale }}" value="{{ old('name.'.$locale, $category->getTranslation('name', $locale, false)) }}" required maxlength="80"
                   aria-invalid="{{ $errors->has('name.'.$locale) ? 'true' : 'false' }}" aria-describedby="name_{{ $locale }}_error"
                   class="mt-2 w-full rounded-md border-slate-300 focus:border-brand-primary focus:ring-brand-primary">
            <div id="name_{{ $locale }}_error"><x-input-error :messages="$errors->get('name.'.$locale)" class="mt-2" /></div>
        </div>
    @endforeach
    <div class="sm:col-span-2">
        <label for="slug" class="block text-sm font-semibold">{{ __('admin.fields.slug') }}</label>
        <input id="slug" name="slug" value="{{ old('slug', $category->slug) }}" required maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" aria-invalid="{{ $errors->has('slug') ? 'true' : 'false' }}" aria-describedby="slug_help slug_error" class="mt-2 w-full rounded-md border-slate-300 focus:border-brand-primary focus:ring-brand-primary">
        <p id="slug_help" class="mt-2 text-sm text-slate-600">{{ __('admin.slug_help') }}</p>
        <div id="slug_error"><x-input-error :messages="$errors->get('slug')" class="mt-2" /></div>
    </div>
    <div x-data="{ color: @js(old('color', $category->color)) }">
        <label for="color" class="block text-sm font-semibold">{{ __('admin.fields.color') }}</label>
        <div class="mt-2 flex gap-3">
            <input type="color" :value="/^#[0-9a-f]{6}$/i.test(color) ? color : '#2E6FD8'" @input="color = $event.target.value.toUpperCase()" aria-label="{{ __('admin.color_picker') }}" class="h-11 w-14 shrink-0 rounded-md border-slate-300 p-1 focus:ring-brand-primary">
            <input id="color" name="color" value="{{ old('color', $category->color) }}" x-model="color" required pattern="#[0-9A-Fa-f]{6}" maxlength="7" aria-invalid="{{ $errors->has('color') ? 'true' : 'false' }}" aria-describedby="color_error" class="min-w-0 flex-1 rounded-md border-slate-300 focus:border-brand-primary focus:ring-brand-primary">
        </div>
        <div id="color_error"><x-input-error :messages="$errors->get('color')" class="mt-2" /></div>
    </div>
    <div>
        <label for="sort" class="block text-sm font-semibold">{{ __('admin.fields.sort') }}</label>
        <input id="sort" name="sort" type="number" min="0" max="2147483647" step="1" value="{{ old('sort', $category->sort) }}" required aria-invalid="{{ $errors->has('sort') ? 'true' : 'false' }}" aria-describedby="sort_error" class="mt-2 w-full rounded-md border-slate-300 focus:border-brand-primary focus:ring-brand-primary">
        <div id="sort_error"><x-input-error :messages="$errors->get('sort')" class="mt-2" /></div>
    </div>
    <div class="sm:col-span-2">
        <label for="icon" class="block text-sm font-semibold">{{ __('admin.fields.icon') }}</label>
        @if (! empty($icon))
            <span aria-hidden="true" class="mt-3 block h-10 w-10 [&_svg]:h-full [&_svg]:w-full">{!! $icon !!}</span>
        @endif
        <input id="icon" name="icon" type="file" accept=".svg,image/svg+xml" @required(! $category->exists) aria-invalid="{{ $errors->has('icon') ? 'true' : 'false' }}" aria-describedby="icon_help icon_error" class="mt-2 block w-full rounded-md text-sm file:mr-4 file:min-h-11 file:rounded-md file:border-0 file:bg-brand-soft file:px-4 file:font-semibold file:text-brand-ink focus-visible:ring-2 focus-visible:ring-brand-primary">
        <p id="icon_help" class="mt-2 text-sm text-slate-600">{{ __('admin.icon_help') }}</p>
        <div id="icon_error"><x-input-error :messages="$errors->get('icon')" class="mt-2" /></div>
    </div>
    <div class="sm:col-span-2">
        <label for="is_active" class="inline-flex min-h-11 items-center gap-3 text-sm font-semibold">
            <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $category->is_active)) aria-describedby="is_active_error" aria-invalid="{{ $errors->has('is_active') ? 'true' : 'false' }}" class="rounded border-slate-300 text-brand-primary focus:ring-brand-primary">
            {{ __('admin.fields.is_active') }}
        </label>
        <div id="is_active_error"><x-input-error :messages="$errors->get('is_active')" class="mt-2" /></div>
    </div>
</div>
<div class="mt-8 flex flex-wrap items-center gap-4 border-t border-slate-200 pt-6">
    <button class="min-h-11 rounded-md bg-brand-primary px-5 font-semibold text-white hover:bg-blue-700 focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">{{ __('admin.save') }}</button>
    <a href="{{ route('admin.categories.index') }}" class="inline-flex min-h-11 items-center rounded px-3 underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('admin.cancel') }}</a>
</div>
