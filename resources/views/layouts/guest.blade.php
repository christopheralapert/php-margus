<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('app.title') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <a href="#main" class="skip-link">{{ __('wizard.skip') }}</a>
        <x-demo-banner />
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <x-lang-switcher class="mb-6" />
            <header>
                <a href="{{ route('home', ['locale' => app()->getLocale()]) }}" aria-label="{{ __('app.title') }}">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </header>

            <main id="main" tabindex="-1" class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </main>
            <footer class="py-6"><a href="{{ route('privacy') }}" class="inline-flex min-h-11 items-center underline">{{ __('privacy.title') }}</a></footer>
        </div>
    </body>
</html>
