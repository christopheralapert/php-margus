@extends('layouts.patient')

@section('content')
    <section class="max-w-3xl">
        <h1 class="max-w-2xl text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">{{ __('wizard.headline') }}</h1>
        <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-600">{{ __('wizard.intro') }}</p>
        <a href="{{ route('wizard.procedures') }}" class="mt-8 inline-flex min-h-14 items-center justify-center gap-6 rounded-xl bg-brand-primary px-8 py-4 text-lg font-semibold text-white hover:bg-blue-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-4">
            {{ __('wizard.start') }}
            <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
        </a>
        <p class="mt-4 text-sm leading-relaxed text-slate-600">{{ __('wizard.privacy_note') }}</p>
    </section>

    <section class="mt-14 border-t border-slate-200 pt-8" aria-labelledby="how-title">
        <h2 id="how-title" class="text-xl font-semibold">{{ __('wizard.how_it_works') }}</h2>
        <ol class="mt-6 grid gap-6 md:grid-cols-3">
            @foreach (__('wizard.steps') as $step)
                <li class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-soft font-semibold text-brand-ink">{{ $loop->iteration }}</span>
                    <span class="font-medium">{{ $step }}</span>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="mt-12 rounded-2xl bg-brand-soft p-6 sm:p-8" aria-labelledby="language-title">
        <h2 id="language-title" class="text-xl font-semibold">{{ __('wizard.language') }}</h2>
        <div class="mt-4 flex flex-col gap-3 sm:flex-row">
            @foreach (config('app.supported_locales') as $locale)
                <a href="{{ route('home', ['locale' => $locale]) }}" lang="{{ $locale }}" hreflang="{{ $locale }}"
                   @if (app()->getLocale() === $locale) aria-current="page" @endif
                   class="inline-flex min-h-14 items-center justify-center rounded-xl border border-slate-300 bg-white px-8 py-3 font-semibold hover:border-brand-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">
                    {{ __('wizard.languages.'.$locale) }}
                </a>
            @endforeach
        </div>
    </section>
@endsection
