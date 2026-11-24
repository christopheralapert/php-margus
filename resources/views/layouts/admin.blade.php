<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · {{ __('admin.title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-brand-soft font-sans text-brand-ink antialiased selection:bg-brand-primary selection:text-white">
    <a href="#main" class="sr-only focus:not-sr-only focus:inline-block focus:p-4">{{ __('admin.skip') }}</a>
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-8">
            <a href="{{ route('admin.dashboard') }}" class="flex min-h-11 items-center gap-4 rounded focus-visible:ring-2 focus-visible:ring-brand-primary">
                <img src="{{ asset('brand/logo.svg') }}" alt="{{ __('admin.brand') }}" class="h-10 w-28">
                <span class="border-l border-slate-200 pl-4 text-sm font-semibold">{{ __('admin.title') }}</span>
            </a>
            <div class="flex flex-wrap items-center gap-4">
                <x-lang-switcher class="[&_a]:inline-flex [&_a]:min-h-11 [&_a]:items-center [&_a]:px-2" />
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="min-h-11 rounded-md border border-slate-300 px-4 text-sm font-semibold hover:bg-brand-soft focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('admin.logout') }}</button>
                </form>
            </div>
        </div>
        <nav aria-label="{{ __('admin.navigation') }}" class="mx-auto flex max-w-7xl flex-wrap gap-x-6 gap-y-1 px-4 sm:px-8">
            @foreach (['dashboard', 'categories'] as $page)
                @php($active = request()->routeIs($page === 'dashboard' ? 'admin.dashboard' : 'admin.categories.*'))
                <a href="{{ route($page === 'dashboard' ? 'admin.dashboard' : 'admin.categories.index') }}"
                   @if ($active) aria-current="page" @endif
                   @class(['inline-flex min-h-11 items-center border-b-2 text-sm font-semibold focus-visible:ring-2 focus-visible:ring-brand-primary', 'border-brand-primary text-brand-primary' => $active, 'border-transparent hover:border-slate-300' => ! $active])>{{ __('admin.'.$page) }}</a>
            @endforeach
            @foreach (['procedures', 'settings'] as $page)
                <span aria-disabled="true" class="inline-flex min-h-11 items-center gap-2 text-sm text-slate-500">{{ __('admin.'.$page) }} <span class="text-xs">{{ __('admin.coming_soon') }}</span></span>
            @endforeach
        </nav>
    </header>
    <main id="main" class="mx-auto max-w-7xl px-4 py-8 sm:px-8 sm:py-12">
        @if (session('success'))
            <p role="status" class="mb-6 rounded-lg bg-emerald-100 p-4 text-emerald-900">{{ session('success') }}</p>
        @endif
        @if (session('error') || $errors->any())
            <div role="alert" class="mb-6 rounded-lg bg-red-100 p-4 text-red-900">
                <p>{{ session('error') ?? __('admin.errors') }}</p>
                @error('icon') <p class="mt-1">{{ $message }}</p> @enderror
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
