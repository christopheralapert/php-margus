@extends('layouts.patient')
@inject('settings', 'App\Support\Settings')

@section('content')
    <x-wizard-progress :step="1" />
    @php
        $selected = old('procedures', session('wizard.procedures', []));
        $selected = is_array($selected) ? array_values(array_filter($selected, 'is_scalar')) : [];
        $groups = $categories->mapWithKeys(fn ($category) => [
            $category->id => $category->procedures->map(fn ($procedure) => $procedure->name.' '.$procedure->search_keywords)->all(),
        ])->all();
    @endphp
    <h1 class="max-w-2xl text-3xl font-semibold leading-tight tracking-tight sm:text-4xl">{{ __('wizard.picker_title') }}</h1>
    <p class="mt-4 max-w-prose leading-relaxed text-slate-600">{{ __('wizard.picker_intro') }}</p>

    <form method="POST" action="{{ route('wizard.procedures.store') }}" class="mt-8" x-data="procedurePicker({{ Js::from($groups) }}, {{ Js::from($selected) }})">
        @csrf
        @if ($errors->any())
            <div role="alert" id="selection-errors" class="mb-6 rounded-xl border border-brand-danger p-4">
                <h2 class="font-semibold text-brand-danger">{{ __('wizard.errors') }}</h2>
                <ul class="mt-2 list-inside list-disc text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div x-cloak x-show="true" class="mb-8 max-w-xl">
            <label for="procedure-search" class="mb-2 block font-semibold">{{ __('wizard.search') }}</label>
            <input id="procedure-search" x-ref="search" type="search" x-model="query" placeholder="{{ __('wizard.search_hint') }}"
                   class="min-h-12 w-full rounded-xl border-slate-400 placeholder:text-slate-500 focus:border-brand-primary focus:ring-brand-primary">
            <p class="mt-2 text-sm text-slate-600" aria-live="polite" aria-atomic="true" x-text="{{ Js::from(__('wizard.results')) }}.replace(':count', resultCount)"></p>
        </div>

        <div class="space-y-10">
            @forelse ($categories as $category)
                <section x-show="categoryVisible({{ $category->id }})" aria-labelledby="category-{{ $category->id }}">
                    <div class="mb-4 flex items-center gap-3">
                        <x-category-icon :slug="$category->slug" :color="$category->color" />
                        <h2 id="category-{{ $category->id }}" class="text-xl font-semibold">{{ $category->name }}</h2>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($category->procedures as $procedure)
                            <label for="procedure-{{ $procedure->id }}"
                                   x-show="matches({{ Js::from($procedure->name.' '.$procedure->search_keywords) }})"
                                   class="flex min-w-0 cursor-pointer items-start gap-4 rounded-xl border border-l-4 border-slate-300 bg-white p-5 hover:border-slate-500 focus-within:ring-2 focus-within:ring-brand-primary focus-within:ring-offset-2 has-[:checked]:border-brand-primary"
                                   style="border-left-color: {{ $procedure->themeColor() }}">
                                <input id="procedure-{{ $procedure->id }}" type="checkbox" name="procedures[]" value="{{ $procedure->id }}"
                                       x-model="selected" @checked(in_array($procedure->id, $selected))
                                       @if ($errors->any()) aria-describedby="selection-errors" aria-invalid="true" @endif
                                       class="mt-1 h-5 w-5 shrink-0 rounded border-slate-400 text-brand-primary focus:ring-brand-primary">
                                <span class="min-w-0 break-words">
                                    <span class="block font-semibold" style="color: {{ (new \App\Services\Leaflet\ThemeColor($procedure->themeColor()))->dark() }}">{{ $procedure->name }}</span>
                                    <span class="mt-1 block text-sm leading-relaxed text-slate-600">{{ $procedure->summary }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="rounded-xl bg-brand-soft p-6">
                    <p>{{ __('wizard.empty') }}</p>
                    <a href="tel:{{ str_replace(' ', '', $settings->get('clinic.phone')) }}" class="mt-3 inline-flex min-h-11 items-center underline">{{ __('wizard.phone') }}: {{ $settings->get('clinic.phone') }}</a>
                </div>
            @endforelse
        </div>

        @if ($categories->isNotEmpty())
            <div x-cloak x-show="resultCount === 0" class="rounded-xl bg-brand-soft p-6 text-center">
                <p>{{ __('wizard.no_results') }}</p>
                <button type="button" @click="query = ''; $refs.search.focus()" class="mt-3 min-h-11 rounded px-4 font-semibold text-brand-primary underline focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('wizard.clear_search') }}</button>
            </div>
        @endif

        @if ($categories->isNotEmpty())
        <div class="sticky bottom-0 z-10 mt-10 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-white py-4">
            <p class="font-semibold" aria-live="polite" aria-atomic="true" x-text="{{ Js::from(__('wizard.selected')) }}.replace(':count', selected.length)">{{ __('wizard.selected', ['count' => count($selected)]) }}</p>
            <button type="submit" :disabled="selected.length === 0" class="min-h-12 rounded-xl bg-brand-primary px-7 py-3 font-semibold text-white hover:bg-blue-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-600">{{ __('wizard.continue') }}</button>
        </div>
        @endif
    </form>
@endsection
