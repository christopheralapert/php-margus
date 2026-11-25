<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('app.title') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-100 font-sans text-gray-900 antialiased">
        <main class="mx-auto max-w-3xl px-6 py-12">
            <x-lang-switcher class="mb-6" />
            <h1 class="text-3xl font-semibold">{{ __('app.title') }}</h1>
        </main>
    </body>
</html>
