@inject('settings', 'App\Support\Settings')
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.title') }} · {{ $settings->localized('clinic.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-brand-ink antialiased selection:bg-brand-primary selection:text-white">
    <a href="#main" class="sr-only focus:not-sr-only focus:p-4 focus:ring-2 focus:ring-brand-primary">{{ __('wizard.skip') }}</a>
    <header class="border-b border-slate-200">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-4 sm:px-8">
            <a href="{{ route('home') }}" class="flex min-h-11 min-w-0 items-center rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary">
                <img src="{{ asset('brand/logo.svg') }}" alt="{{ $settings->localized('clinic.name') }}" class="h-auto max-h-12 w-32 sm:w-40">
            </a>
            <div class="flex flex-wrap items-center justify-end gap-x-4">
                @auth
                    <a href="{{ route('account.leaflets') }}" class="inline-flex min-h-11 items-center rounded text-sm font-semibold text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('account.title') }}</a>
                @endauth
                <x-lang-switcher class="shrink-0 gap-1 [&_a]:inline-flex [&_a]:min-h-11 [&_a]:items-center [&_a]:px-3" />
            </div>
        </div>
    </header>
    <main id="main" class="mx-auto w-full max-w-5xl flex-1 px-4 py-10 sm:px-8 sm:py-16">
        @yield('content')
    </main>
    <footer class="border-t border-slate-200 bg-brand-soft">
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-8">
            <div class="flex flex-wrap gap-x-8 gap-y-2 text-sm font-semibold">
                <a href="tel:{{ str_replace(' ', '', $settings->get('clinic.phone')) }}" class="inline-flex min-h-11 items-center gap-2 rounded underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">
                    {{ __('wizard.phone') }} <span>{{ $settings->get('clinic.phone') }}</span>
                </a>
                <a href="tel:{{ $settings->get('clinic.emergency') }}" class="inline-flex min-h-11 items-center gap-2 rounded underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">
                    {{ __('wizard.emergency') }} <span>{{ $settings->get('clinic.emergency') }}</span>
                </a>
            </div>
            <p class="mt-3 max-w-prose text-sm leading-relaxed text-slate-600">{{ $settings->localized('leaflet.disclaimer') }}</p>
        </div>
    </footer>
</body>
</html>
