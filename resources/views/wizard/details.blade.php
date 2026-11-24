@extends('layouts.patient')

@section('content')
    <h1 class="text-3xl font-semibold">{{ __('wizard.details_title') }}</h1>
    <p class="mt-4 text-slate-600">{{ __('wizard.details_placeholder') }}</p>
    <ul class="mt-6 list-inside list-disc space-y-3">
        @foreach ($procedures as $procedure)
            <li>{{ $procedure->name }}</li>
        @endforeach
    </ul>
    <a href="{{ route('wizard.procedures') }}" class="mt-8 inline-flex min-h-11 items-center rounded font-semibold text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('wizard.back') }}</a>
@endsection
