@extends('layouts.patient')

@section('content')
    <h1 class="font-heading text-3xl font-bold">{{ __('pdf.title') }}</h1>
    <p class="mt-4 rounded-xl bg-brand-soft p-4">{{ __('pdf.online_soon') }}</p>
    @foreach ($procedures as $procedure)
        <section class="mt-8 max-w-prose">
            <h2 class="font-heading text-2xl font-bold">{{ $procedure->getTranslation('name', app()->getLocale()) }}</h2>
            <p class="mt-3 leading-relaxed">{{ $procedure->getTranslation('summary', app()->getLocale()) }}</p>
        </section>
    @endforeach
@endsection
