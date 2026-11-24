@extends('layouts.patient')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-3xl font-semibold sm:text-4xl">{{ __('account.title') }}</h1>
        <a href="{{ route('wizard.procedures') }}" class="inline-flex min-h-11 items-center rounded font-semibold text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('account.start') }}</a>
    </div>

    @if (session('status'))
        <p role="status" class="mt-6 rounded-xl bg-brand-soft p-4">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <div role="alert" class="mt-6 rounded-xl border border-red-300 bg-red-50 p-4 text-red-900">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="mt-8 divide-y divide-slate-200">
        @forelse ($leaflets as $leaflet)
            <article class="py-8 first:pt-0">
                <h2 class="break-words text-xl font-semibold">
                    @foreach ($leaflet->procedure_ids as $id)
                        {{ $procedures->get($id)?->getTranslation('name', app()->getLocale()) ?? __('account.removed') }}@if (! $loop->last), @endif
                    @endforeach
                </h2>
                <dl class="mt-4 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                    <div><dt class="text-slate-600">{{ __('account.locale') }}</dt><dd class="mt-1">{{ __('account.languages.'.$leaflet->locale) }}</dd></div>
                    <div><dt class="text-slate-600">{{ __('account.created') }}</dt><dd class="mt-1 tabular-nums"><time datetime="{{ $leaflet->created_at->toIso8601String() }}">{{ $leaflet->created_at->format('d.m.Y H:i') }}</time></dd></div>
                    <div><dt class="text-slate-600">{{ __('account.last_downloaded') }}</dt><dd class="mt-1 tabular-nums">{{ $leaflet->last_downloaded_at?->format('d.m.Y H:i') ?? __('account.never') }}</dd></div>
                </dl>
                <a href="{{ route('account.leaflets.pdf', ['leaflet' => $leaflet->uuid]) }}" class="mt-5 inline-flex min-h-11 items-center rounded-xl bg-brand-primary px-5 py-3 font-semibold text-white hover:bg-blue-800 focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">{{ __('account.download') }}</a>
                <form method="POST" action="{{ route('account.leaflets.destroy', ['leaflet' => $leaflet->uuid]) }}" class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2">
                    @csrf
                    @method('DELETE')
                    <label for="confirm-{{ $leaflet->uuid }}" class="flex min-h-11 items-center gap-3 text-sm">
                        <input id="confirm-{{ $leaflet->uuid }}" type="checkbox" name="confirm" value="1" required class="rounded border-slate-400 text-brand-primary focus:ring-brand-primary">
                        {{ __('account.confirm') }}
                    </label>
                    <button type="submit" class="min-h-11 rounded px-3 font-semibold text-red-800 underline underline-offset-4 hover:bg-red-50 focus-visible:ring-2 focus-visible:ring-red-800">{{ __('account.delete') }}</button>
                </form>
            </article>
        @empty
            <p class="py-8 text-slate-600">{{ __('account.empty') }}</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $leaflets->links() }}</div>
@endsection
