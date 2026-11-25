@extends('layouts.patient')
@inject('settings', 'App\Support\Settings')

@section('title', $title)
@section('meta')
    <meta name="robots" content="noindex">
@endsection

@section('content')
    <h1 class="break-words text-3xl font-semibold leading-tight sm:text-4xl">{{ $title }}</h1>
    @if ($doc->showDraftMarks())
        <p class="mt-6 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">{{ __('wizard.draft') }}</p>
    @endif
    <a href="{{ route('online.personalize', ['combo' => request()->route('combo')]) }}" class="mt-6 inline-flex min-h-11 items-center rounded font-semibold text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('wizard.make_pdf') }}</a>

    @include('leaflet._content')

    @if (config('chat.enabled'))
        @include('online._chat')
    @endif

    <div class="sticky bottom-0 z-10 mt-10 border-t border-slate-200 bg-white py-3 sm:hidden">
        <a href="tel:{{ str_replace(' ', '', $settings->get('clinic.phone')) }}" class="flex min-h-12 items-center justify-center rounded-xl bg-brand-primary px-5 py-3 font-semibold text-white hover:bg-blue-800 focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">{{ __('wizard.phone') }} · {{ $settings->get('clinic.phone') }}</a>
    </div>
@endsection
