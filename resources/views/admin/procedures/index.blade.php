@extends('layouts.admin')
@section('title', __('admin.procedures'))
@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div><h1 class="text-3xl font-bold tracking-tight">{{ __('admin.procedures') }}</h1><p class="mt-2 text-slate-600">{{ __('admin.procedure.index_help') }}</p></div>
        <a href="{{ route('admin.procedures.create') }}" class="inline-flex min-h-11 items-center rounded-md bg-brand-primary px-5 font-semibold text-white hover:bg-blue-700 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('admin.procedure.new') }}</a>
    </div>
    <form method="GET" action="{{ route('admin.procedures.index') }}" class="mt-8 flex flex-wrap items-end gap-4">
        <label class="block text-sm font-semibold">{{ __('admin.procedure.category_id') }}
            <select name="category_id" class="mt-2 block w-full rounded-md border-slate-300 focus:ring-brand-primary">
                <option value="">{{ __('admin.procedure.all') }}</option>
                @foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach
            </select>
        </label>
        <label class="block text-sm font-semibold">{{ __('admin.procedure.status') }}
            <select name="status" class="mt-2 block w-full rounded-md border-slate-300 focus:ring-brand-primary">
                <option value="">{{ __('admin.procedure.all') }}</option>
                @foreach ($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach
            </select>
        </label>
        <label class="block text-sm font-semibold">{{ __('admin.procedure.search') }}
            <input type="search" name="search" value="{{ request('search') }}" maxlength="255" class="mt-2 block w-full rounded-md border-slate-300 focus:ring-brand-primary">
        </label>
        <button class="min-h-11 rounded-md border border-slate-300 bg-white px-4 font-semibold hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('admin.procedure.filter') }}</button>
    </form>
    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">{{ __('admin.procedure.index_help') }}</caption>
            <thead class="border-b border-slate-200 bg-slate-50 text-slate-600"><tr>
                @foreach (['fields.color', 'fields.name_et', 'fields.name_en', 'procedure.category_id', 'procedure.status', 'active', 'procedure.updated_at', 'actions'] as $heading)<th scope="col" class="whitespace-nowrap px-4 py-4 font-semibold">{{ __('admin.'.$heading) }}</th>@endforeach
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($procedures as $procedure)
                    @php($color = preg_match('/^#[a-f0-9]{6}$/iD', $procedure->themeColor()) ? $procedure->themeColor() : '#1F2937')
                    <tr>
                        <td class="px-4 py-4"><span class="block h-5 w-5 rounded-full border border-slate-300" style="background-color: {{ $color }}" title="{{ $color }}"></span></td>
                        <th scope="row" class="min-w-40 px-4 py-4 font-semibold" lang="et">{{ $procedure->getTranslation('name', 'et', false) }}</th>
                        <td class="min-w-40 px-4 py-4" lang="en">{{ $procedure->getTranslation('name', 'en', false) }}</td>
                        <td class="px-4 py-4">{{ $procedure->category->name }}</td>
                        <td class="px-4 py-4"><span @class(['whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-amber-100 text-amber-900' => $procedure->status->value === 'draft', 'bg-blue-100 text-blue-900' => $procedure->status->value === 'reviewed', 'bg-emerald-100 text-emerald-900' => $procedure->status->value === 'published'])>{{ $procedure->status->label() }}</span></td>
                        <td class="px-4 py-4">{{ __($procedure->is_active ? 'admin.active' : 'admin.inactive') }}</td>
                        <td class="whitespace-nowrap px-4 py-4 tabular-nums">{{ $procedure->updated_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-4"><div class="flex items-center gap-3">
                            <a href="{{ route('admin.procedures.edit', $procedure) }}" class="inline-flex min-h-11 items-center rounded text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('admin.procedure.edit') }}</a>
                            <form method="POST" action="{{ route('admin.procedures.destroy', $procedure) }}" x-data @submit="if (! window.confirm(@js(__('admin.procedure.delete_confirm')))) $event.preventDefault()">
                                @csrf @method('DELETE')
                                <button class="min-h-11 rounded px-2 text-red-700 underline underline-offset-4 hover:bg-red-50 focus-visible:ring-2 focus-visible:ring-red-700">{{ __('admin.procedure.delete') }}</button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-slate-600">{{ __('admin.procedure.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $procedures->links() }}</div>
@endsection
