@extends('layouts.admin')
@section('title', __($procedure->exists ? 'admin.procedure.edit' : 'admin.procedure.new'))
@section('content')
    @php
        $categoryColors = $categories->pluck('color', 'id');
        $selectedCategory = old('category_id', $procedure->category_id ?? $categories->first()?->id);
        $resolvedColor = old('color', $procedure->color) ?: ($categoryColors[$selectedCategory] ?? '#2E6FD8');
    @endphp
    <h1 class="text-3xl font-bold tracking-tight">{{ __($procedure->exists ? 'admin.procedure.edit' : 'admin.procedure.new') }}</h1>
    <p class="mt-2 max-w-3xl text-slate-600">{{ __('admin.procedure.form_help') }}</p>
    @if ($procedure->exists)
        <div class="mt-4 flex flex-wrap gap-3">
            @foreach (['et', 'en'] as $previewLocale)
                <a href="{{ route('admin.procedures.preview', ['procedure' => $procedure, 'lang' => $previewLocale]) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-md border border-slate-300 bg-white px-4 font-semibold text-brand-primary hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('admin.procedure.preview', ['lang' => $previewLocale]) }}</a>
                <a href="{{ route('admin.procedures.preview', ['procedure' => $procedure, 'lang' => $previewLocale, 'print' => 1]) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded px-2 text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('admin.procedure.preview_print', ['lang' => $previewLocale]) }}</a>
            @endforeach
        </div>
    @endif
    @if ($errors->any())
        <ul id="procedure-errors" role="alert" class="mt-6 list-disc space-y-2 rounded-lg bg-red-50 p-6 text-red-900">
            @foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach
        </ul>
    @else
        <div id="procedure-errors" hidden></div>
    @endif
    <form method="POST" action="{{ $procedure->exists ? route('admin.procedures.update', $procedure) : route('admin.procedures.store') }}" enctype="multipart/form-data" class="mt-8 max-w-4xl space-y-10" x-data="{ category: @js((string) $selectedCategory), color: @js(old('color', $procedure->color) ?? ''), colors: @js($categoryColors) }">
        @csrf
        @if ($procedure->exists) @method('PUT') @endif
        <fieldset class="space-y-6 rounded-xl border border-slate-200 bg-white p-5 sm:p-8">
            <legend class="px-2 text-xl font-bold">{{ __('admin.procedure.basics') }}</legend>
            <div class="grid gap-6 sm:grid-cols-2">
                <label class="block text-sm font-semibold">{{ __('admin.procedure.category_id') }}
                    <select aria-describedby="procedure-errors" aria-invalid="{{ $errors->has('category_id') ? 'true' : 'false' }}" name="category_id" x-model="category" required class="mt-2 w-full rounded-md border-slate-300 focus:ring-brand-primary">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $selectedCategory === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-semibold">{{ __('admin.fields.slug') }}
                    <input aria-describedby="procedure-errors" aria-invalid="{{ $errors->has('slug') ? 'true' : 'false' }}" name="slug" value="{{ old('slug', $procedure->slug) }}" required maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" class="mt-2 w-full rounded-md border-slate-300 focus:ring-brand-primary">
                    <span class="mt-2 block font-normal text-slate-600">{{ __('admin.slug_help') }}</span>
                </label>
                <label class="block text-sm font-semibold">{{ __('admin.fields.sort') }}
                    <input type="number" aria-describedby="procedure-errors" aria-invalid="{{ $errors->has('sort') ? 'true' : 'false' }}" name="sort" value="{{ old('sort', $procedure->sort) }}" min="0" max="2147483647" required class="mt-2 w-full rounded-md border-slate-300 focus:ring-brand-primary">
                </label>
                <label class="flex min-h-11 items-center gap-3 text-sm font-semibold">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" aria-describedby="procedure-errors" aria-invalid="{{ $errors->has('is_active') ? 'true' : 'false' }}" name="is_active" value="1" @checked(old('is_active', $procedure->is_active)) class="rounded border-slate-300 text-brand-primary focus:ring-brand-primary">{{ __('admin.fields.is_active') }}
                </label>
                <label class="block text-sm font-semibold sm:col-span-2">{{ __('admin.fields.color') }}
                    <span class="mt-2 flex items-center gap-3">
                        <input aria-describedby="procedure-errors" aria-invalid="{{ $errors->has('color') ? 'true' : 'false' }}" name="color" x-model="color" value="{{ old('color', $procedure->color) }}" pattern="#[a-fA-F0-9]{6}" maxlength="7" class="w-40 rounded-md border-slate-300 focus:ring-brand-primary">
                        <span aria-hidden="true" class="h-8 w-8 rounded-full border border-slate-300" style="background-color: {{ preg_match('/^#[a-f0-9]{6}$/iD', $resolvedColor) ? $resolvedColor : '#2E6FD8' }}" :style="{ backgroundColor: /^#[a-f0-9]{6}$/i.test(color) ? color : colors[category] }"></span>
                        <span class="font-normal text-slate-600">{{ __('admin.procedure.color_help') }}</span>
                    </span>
                </label>
            </div>
            @foreach (['name', 'summary', 'search_keywords', 'follow_up'] as $field)
                @include('admin.procedures._translation', ['field' => $field, 'label' => $field === 'name' ? __('admin.fields.name') : __('admin.procedure.'.$field), 'translations' => $procedure->getTranslations($field)])
            @endforeach
        </fieldset>
        <fieldset class="space-y-8 rounded-xl border border-slate-200 bg-white p-5 sm:p-8">
            <legend class="px-2 text-xl font-bold">{{ __('admin.procedure.timeline_steps') }}</legend>
            @foreach ($phases as $phase)
                @php($step = $procedure->timelineSteps->firstWhere('phase', $phase))
                <section class="space-y-5 border-b border-slate-200 pb-8 last:border-0 last:pb-0">
                    <h2 class="text-lg font-bold">{{ $phase->label() }}</h2>
                    @foreach (['marker', 'title', 'body'] as $field)
                        @include('admin.procedures._translation', ['field' => 'timeline_steps.'.$phase->value.'.'.$field, 'label' => __('admin.procedure.'.$field), 'translations' => $step?->getTranslations($field) ?? []])
                    @endforeach
                </section>
            @endforeach
        </fieldset>
        <fieldset class="space-y-8 rounded-xl border border-slate-200 bg-white p-5 sm:p-8">
            <legend class="px-2 text-xl font-bold">{{ __('admin.procedure.advice_items') }}</legend>
            <p class="text-sm text-slate-600">{{ __('admin.procedure.advice_help') }}</p>
            <datalist id="merge_keys">@foreach ($mergeKeys as $key)<option value="{{ $key }}">@endforeach</datalist>
            @foreach ($sections as $section)
                @include('admin.procedures._advice', ['section' => $section])
            @endforeach
        </fieldset>
        <fieldset class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 sm:p-8">
            <legend class="px-2 text-xl font-bold">{{ __('admin.procedure.illustration') }}</legend>
            @if ($procedure->illustration_path && preg_match('~^illustrations/[a-z0-9/-]+\.(svg|png)$~D', $procedure->illustration_path))
                <img src="{{ asset($procedure->illustration_path) }}" alt="{{ __('admin.procedure.current_illustration') }}" class="h-40 max-w-full object-contain">
            @endif
            <label class="block text-sm font-semibold">{{ __('admin.procedure.illustration') }}
                <input type="file" aria-describedby="procedure-errors" aria-invalid="{{ $errors->has('illustration') ? 'true' : 'false' }}" name="illustration" accept=".svg,.png" class="mt-2 block max-w-full text-sm">
            </label>
            <p class="text-sm text-slate-600">{{ __('admin.procedure.illustration_help') }}</p>
        </fieldset>
        <fieldset class="space-y-6 rounded-xl border border-slate-200 bg-white p-5 sm:p-8">
            <legend class="px-2 text-xl font-bold">{{ __('admin.procedure.review') }}</legend>
            <p class="text-sm text-slate-600">{{ __('admin.procedure.workflow_help') }}</p>
            <div class="grid gap-6 sm:grid-cols-2">
                <label class="block text-sm font-semibold">{{ __('admin.procedure.status') }}
                    <select aria-describedby="procedure-errors" aria-invalid="{{ $errors->has('status') ? 'true' : 'false' }}" name="status" class="mt-2 w-full rounded-md border-slate-300 focus:ring-brand-primary">
                        @foreach ($statuses as $status)<option value="{{ $status->value }}" @selected(old('status', $procedure->status->value) === $status->value)>{{ $status->label() }}</option>@endforeach
                    </select>
                </label>
                <label class="block text-sm font-semibold">{{ __('admin.procedure.reviewed_by') }}
                    <input aria-describedby="procedure-errors" aria-invalid="{{ $errors->has('reviewed_by') ? 'true' : 'false' }}" name="reviewed_by" value="{{ old('reviewed_by', $procedure->reviewed_by) }}" maxlength="255" class="mt-2 w-full rounded-md border-slate-300 focus:ring-brand-primary">
                </label>
            </div>
            @if ($procedure->reviewed_at)<p class="text-sm text-slate-600">{{ __('admin.procedure.reviewed_at') }}: {{ $procedure->reviewed_at->format('Y-m-d H:i') }}</p>@endif
            @include('admin.procedures._translation', ['field' => 'review_note', 'label' => __('admin.procedure.review_note'), 'translations' => $procedure->getTranslations('review_note')])
        </fieldset>
        <div class="flex items-center gap-6 pb-8">
            <button class="min-h-11 rounded-md bg-brand-primary px-6 font-semibold text-white hover:bg-blue-700 focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">{{ __('admin.procedure.save') }}</button>
            <a href="{{ route('admin.procedures.index') }}" class="inline-flex min-h-11 items-center rounded underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('admin.cancel') }}</a>
        </div>
    </form>
@endsection
