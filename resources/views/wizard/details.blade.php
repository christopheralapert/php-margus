@extends('layouts.patient')

@section('content')
    <x-wizard-progress :step="2" />
    <h1 class="text-3xl font-semibold">{{ __('wizard.details_title') }}</h1>
    <p class="mt-4 max-w-prose leading-relaxed text-slate-600">{{ __('wizard.details_intro') }}</p>
    <ul class="mt-6 list-inside list-disc space-y-3">
        @foreach ($doc->sections as $section)
            <li>{{ $section['procedure']->getTranslation('name', $doc->locale) }}</li>
        @endforeach
    </ul>
    <form method="POST" action="{{ route('wizard.details.store') }}" class="mt-8 max-w-xl space-y-6"
          x-data="{ firstName: {{ Js::from(old('first_name', session('wizard.first_name', ''))) }}, date: {{ Js::from(old('date', session('wizard.date', ''))) }} }">
        @csrf
        <div>
            <label for="first_name" class="block font-semibold">{{ __('wizard.first_name') }}</label>
            <input id="first_name" name="first_name" type="text" maxlength="40" autocomplete="given-name" x-model="firstName"
                   value="{{ old('first_name', session('wizard.first_name')) }}" aria-describedby="first_name-error" @error('first_name') aria-invalid="true" @enderror
                   class="mt-2 min-h-12 w-full rounded-xl border-slate-400 focus:border-brand-primary focus:ring-brand-primary">
            <p id="first_name-error" class="mt-2 text-sm text-red-700" role="alert">@error('first_name'){{ $message }}@enderror</p>
        </div>
        <div>
            <label for="date" class="block font-semibold">{{ __('wizard.date') }}</label>
            <input id="date" name="date" type="date" x-model="date" value="{{ old('date', session('wizard.date')) }}"
                   min="{{ today()->subYear()->format('Y-m-d') }}" max="{{ today()->addDay()->format('Y-m-d') }}"
                   aria-describedby="date-error" @error('date') aria-invalid="true" @enderror
                   class="mt-2 min-h-12 w-full rounded-xl border-slate-400 focus:border-brand-primary focus:ring-brand-primary">
            <p id="date-error" class="mt-2 text-sm text-red-700" role="alert">@error('date'){{ $message }}@enderror</p>
        </div>
        <div x-show="firstName || date" class="rounded-xl bg-brand-soft p-5">
            <p id="consent-help" class="text-sm leading-relaxed">{{ __('wizard.consent_help') }}</p>
            <label for="consent" class="mt-4 flex min-h-11 items-start gap-3">
                <input id="consent" name="consent" type="checkbox" value="1" @checked(old('consent', session('wizard.consent')))
                       aria-describedby="consent-help consent-error" @error('consent') aria-invalid="true" @enderror
                       class="mt-1 h-5 w-5 shrink-0 rounded border-slate-400 text-brand-primary focus:ring-brand-primary">
                <span>{{ __('wizard.consent') }}</span>
            </label>
            <p id="consent-error" class="mt-2 text-sm text-red-700" role="alert">@error('consent'){{ $message }}@enderror</p>
        </div>
        <button type="submit" class="min-h-12 rounded-xl bg-brand-primary px-7 py-3 font-semibold text-white hover:bg-blue-800 focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">{{ __('wizard.show_preview') }}</button>
    </form>
    <a href="{{ route('wizard.procedures') }}" class="mt-8 inline-flex min-h-11 items-center rounded font-semibold text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('wizard.back') }}</a>
@endsection
