@php
    $locale = app(\App\Support\ErrorPage::class)->locale(request());
    $clinic = config('clinic.clinic');
    $phone = $clinic['phone'];
    $title = __('errors.'.(! empty($pdfFailure) ? 'pdf' : $status).'.title', [], $locale);
    $message = __('errors.'.(! empty($pdfFailure) ? 'pdf' : $status).'.message', [], $locale);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · {{ $clinic['name'][$locale] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-brand-ink antialiased">
    <a href="#main" class="skip-link">{{ __('errors.skip', [], $locale) }}</a>
    <header class="border-b border-slate-200">
        <div class="mx-auto max-w-5xl px-4 py-4 sm:px-8">
            <a href="{{ route('home', ['locale' => $locale]) }}" class="inline-flex min-h-11 items-center">
                <img src="{{ asset('brand/logo.svg') }}" alt="{{ $clinic['name'][$locale] }}" class="w-40">
            </a>
        </div>
    </header>
    <main id="main" tabindex="-1" class="mx-auto w-full max-w-5xl flex-1 px-4 py-16 sm:px-8">
        <h1 class="text-3xl font-semibold sm:text-4xl">{{ $title }}</h1>
        <p class="mt-6 max-w-prose leading-relaxed text-slate-600">{{ $message }}</p>
        <nav aria-label="{{ __('errors.recovery', [], $locale) }}" class="mt-8 flex flex-wrap gap-6">
            <a href="{{ route('home', ['locale' => $locale]) }}" class="inline-flex min-h-12 items-center rounded-xl bg-brand-primary px-6 font-semibold text-white">{{ __('errors.home', [], $locale) }}</a>
        </nav>
    </main>
    <footer class="border-t border-slate-200 bg-brand-soft">
        <div class="mx-auto flex max-w-5xl flex-wrap gap-6 px-4 py-8 sm:px-8">
            <a href="tel:{{ str_replace(' ', '', $phone) }}" class="inline-flex min-h-11 items-center underline underline-offset-4">{{ __('errors.phone', [], $locale) }}: {{ $phone }}</a>
            @if (in_array($status, [500, 503], true))
                <a href="tel:112" class="inline-flex min-h-11 items-center font-semibold text-red-800 underline underline-offset-4">{{ __('errors.emergency', [], $locale) }}</a>
            @endif
        </div>
    </footer>
</body>
</html>
