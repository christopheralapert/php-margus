@extends('layouts.admin')
@section('title', __('admin.dashboard'))
@section('content')
    <h1 class="text-3xl font-bold tracking-tight">{{ __('admin.overview') }}</h1>
    <p class="mt-2 max-w-prose text-slate-600">{{ __('admin.overview_help') }}</p>
    <dl class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <dt class="font-medium"><a href="{{ route('admin.categories.index') }}" class="rounded text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('admin.categories') }}</a></dt>
            <dd class="mt-4 text-4xl font-semibold tabular-nums">{{ $categoryCount }}</dd>
        </div>
        @foreach ($statuses as $status)
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <dt class="font-medium text-slate-600">{{ __('admin.'.$status->value) }}</dt>
                <dd class="mt-4 text-4xl font-semibold tabular-nums">{{ $procedureCounts[$status->value] ?? 0 }}</dd>
            </div>
        @endforeach
    </dl>
@endsection
