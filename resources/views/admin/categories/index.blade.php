@extends('layouts.admin')
@section('title', __('admin.categories'))
@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">{{ __('admin.categories') }}</h1>
            <p class="mt-2 text-slate-600">{{ __('admin.category_help') }}</p>
        </div>
        <a href="{{ route('admin.categories.create') }}" class="inline-flex min-h-11 items-center rounded-md bg-brand-primary px-5 font-semibold text-white hover:bg-blue-700 focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">{{ __('admin.new') }}</a>
    </div>
    <div class="mt-8 overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">{{ __('admin.category_help') }}</caption>
            <thead class="border-b border-slate-200 bg-slate-50 text-slate-600">
                <tr>
                    @foreach (['fields.sort', 'fields.icon', 'fields.name_et', 'fields.name_en', 'fields.color', 'active', 'procedures', 'actions'] as $heading)
                        <th scope="col" class="whitespace-nowrap px-4 py-4 font-semibold">{{ __('admin.'.$heading) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($categories as $category)
                    @php($color = preg_match('/^#[0-9a-f]{6}$/iD', $category->color) ? $category->color : '#1F2937')
                    <tr>
                        <td class="px-4 py-4 tabular-nums">{{ $category->sort }}</td>
                        <td class="px-4 py-4"><span aria-hidden="true" class="block h-8 w-8 [&_svg]:h-full [&_svg]:w-full" style="color: {{ $color }}">{!! $icons[$category->id] !!}</span></td>
                        <th scope="row" class="min-w-40 px-4 py-4 font-semibold"><span lang="et">{{ $category->getTranslation('name', 'et', false) }}</span></th>
                        <td class="min-w-40 px-4 py-4" lang="en">{{ $category->getTranslation('name', 'en', false) }}</td>
                        <td class="px-4 py-4"><span class="inline-flex items-center gap-2 whitespace-nowrap"><span aria-hidden="true" class="h-5 w-5 rounded border border-slate-300" style="background-color: {{ $color }}"></span>{{ $category->color }}</span></td>
                        <td class="px-4 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-100 text-emerald-900' => $category->is_active, 'bg-slate-100 text-slate-700' => ! $category->is_active])>{{ __($category->is_active ? 'admin.active' : 'admin.inactive') }}</span></td>
                        <td class="px-4 py-4 tabular-nums">{{ $category->procedures_count }}</td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="inline-flex min-h-11 items-center rounded text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('admin.edit') }}</a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" x-data @submit="if (! window.confirm(@js(__('admin.delete_confirm')))) $event.preventDefault()">
                                    @csrf
                                    @method('DELETE')
                                    <button class="min-h-11 rounded px-2 text-red-700 underline underline-offset-4 hover:bg-red-50 focus-visible:ring-2 focus-visible:ring-red-700">{{ __('admin.delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-slate-600">{{ __('admin.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
