@extends('layouts.patient')

@section('content')
    <x-wizard-progress :step="3" />
    <h1 class="text-3xl font-semibold sm:text-4xl">{{ $doc->title() }}</h1>
    @include('wizard.personal-details')
    @if ($doc->hasDraft())
        <p class="mt-6 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">{{ __('wizard.draft') }}</p>
    @endif
    <div class="mt-8 flex flex-wrap items-center gap-5">
        <form method="POST" action="{{ route('leaflet.pdf') }}">
            @csrf
            <button type="submit" class="min-h-14 rounded-xl bg-brand-primary px-8 py-4 text-lg font-semibold text-white hover:bg-blue-800 focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">{{ __('wizard.download') }}</button>
        </form>
        <form method="POST" action="{{ route('leaflet.pdf') }}">
            @csrf
            <input type="hidden" name="format" value="trifold">
            <button type="submit" class="min-h-14 rounded-xl border border-brand-primary px-8 py-4 text-lg font-semibold text-brand-primary hover:bg-brand-soft focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">{{ __('wizard.download_trifold') }}</button>
        </form>
        <a href="{{ route('wizard.details') }}" class="inline-flex min-h-11 items-center rounded font-semibold text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('wizard.edit') }}</a>
        @auth
            @if (auth()->user()->hasVerifiedEmail())
                <form method="POST" action="{{ route('account.leaflets.store') }}">
                    @csrf
                    <button type="submit" class="min-h-11 rounded-xl border border-brand-primary px-5 py-3 font-semibold text-brand-primary hover:bg-brand-soft focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">{{ __('account.save') }}</button>
                    <button type="submit" name="format" value="trifold" class="min-h-11 rounded-xl border border-brand-primary px-5 py-3 font-semibold text-brand-primary hover:bg-brand-soft focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">{{ __('wizard.save_trifold') }}</button>
                </form>
            @else
                <a href="{{ route('verification.notice') }}" class="inline-flex min-h-11 items-center rounded text-sm text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('account.verify_to_save') }}</a>
            @endif
        @else
            <a href="{{ route('login', ['save_leaflet' => '1']) }}" class="inline-flex min-h-11 items-center rounded text-sm text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('account.login_to_save') }}</a>
        @endauth
    </div>
    @include('leaflet._content')
@endsection
