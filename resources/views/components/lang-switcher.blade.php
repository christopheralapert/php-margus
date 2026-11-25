@php
    $segments = explode('/', request()->path());
    $query = request()->server('QUERY_STRING');
@endphp

<nav aria-label="{{ __('app.language') }}" {{ $attributes->class(['flex gap-4 text-sm']) }}>
    @foreach (config('app.supported_locales') as $locale)
        @php
            $segments[0] = $locale;
            $href = url(implode('/', $segments)).($query ? '?'.$query : '');
        @endphp
        <a href="{{ $href }}" hreflang="{{ $locale }}" lang="{{ $locale }}"
            @if (app()->getLocale() === $locale) aria-current="true" @endif
            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded underline focus:outline-none focus:ring-2 focus:ring-indigo-500">
            {{ __('app.languages.'.$locale) }}
        </a>
    @endforeach
</nav>
