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
    <body class="font-sans antialiased">
        <a href="#main" class="skip-link">{{ __('wizard.skip') }}</a>
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')
            <x-lang-switcher class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8" />

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main id="main" tabindex="-1">
                {{ $slot }}
            </main>
            <footer class="mx-auto max-w-7xl px-4 py-6"><a href="{{ route('privacy') }}" class="inline-flex min-h-11 items-center underline">{{ __('privacy.title') }}</a></footer>
        </div>
    </body>
</html>
