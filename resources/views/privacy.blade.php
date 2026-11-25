@extends('layouts.patient')

@section('content')
    <article class="max-w-prose space-y-6 leading-relaxed">
        <h1 class="text-3xl font-semibold">{{ __('privacy.title') }}</h1>
        @if (config('leaflet.show_draft_marks'))
            <p class="rounded-xl bg-brand-soft p-4 font-semibold">{{ __('privacy.draft') }}</p>
        @endif
        <section class="space-y-2">
            <h2 class="text-xl font-semibold">{{ __('privacy.controller_title') }}</h2>
            <p>{{ $clinicName }}<br>{{ $clinicAddress }}<br><a href="mailto:{{ $clinicEmail }}" class="underline">{{ $clinicEmail }}</a></p>
        </section>
        @foreach (['data', 'basis', 'retention', 'rights', 'contact'] as $section)
            <section class="space-y-2">
                <h2 class="text-xl font-semibold">{{ __('privacy.'.$section.'_title') }}</h2>
                <p>{{ __('privacy.'.$section, ['months' => $months, 'days' => $days]) }}</p>
                @if ($section === 'rights')
                    @auth
                        <a href="{{ route('profile.edit') }}" class="inline-flex min-h-11 items-center font-semibold underline">{{ __('privacy.delete_title') }}</a>
                    @endauth
                @endif
            </section>
        @endforeach
    </article>
@endsection
