@php
    $sectionKey = $section->value;
    $existing = $procedure->adviceItems->where('section', $section)->map(fn ($item) => ['text' => $item->getTranslations('text'), 'merge_key' => $item->merge_key])->values()->all();
    $blankRow = ['text' => ['et' => '', 'en' => ''], 'merge_key' => ''];
    $adviceRows = old('advice_items.'.$sectionKey, [...$existing, $blankRow, $blankRow]);
    $adviceRows = is_array($adviceRows) ? array_values($adviceRows) : [$blankRow, $blankRow];
@endphp
<section class="space-y-4 border-b border-slate-200 pb-8 last:border-0" x-data="{ ready: false, tab: @js($errors->any() ? null : app()->getLocale()), errors: @js(array_keys($errors->getMessages())), nextId: {{ count($adviceRows) }}, rows: @js(array_map(fn ($row, $id) => [...$row, 'id' => $id], $adviceRows, array_keys($adviceRows))), move(index, delta) { const target = index + delta; if (target >= 0 && target < this.rows.length) { const row = this.rows.splice(index, 1)[0]; this.rows.splice(target, 0, row); } } }" x-init="ready = true">
    <h2 class="text-lg font-bold">{{ $section->label() }}</h2>
    <fieldset x-show="!ready" :disabled="ready" class="space-y-6">
        <legend class="sr-only">{{ $section->label() }}</legend>
        @foreach ($adviceRows as $index => $row)
            <div class="space-y-3">
                @foreach (['et', 'en'] as $language)
                    <label class="block text-sm font-semibold">{{ __('admin.procedure.text') }} — {{ __('admin.procedure.'.$language) }}
                        <textarea aria-describedby="procedure-errors" aria-invalid="{{ $errors->has('advice_items.'.($sectionKey).'.'.($index).'.text.'.($language).'') ? 'true' : 'false' }}" name="advice_items[{{ $sectionKey }}][{{ $index }}][text][{{ $language }}]" lang="{{ $language }}" rows="3" maxlength="10000" class="mt-2 w-full rounded-md border-slate-300 focus:ring-brand-primary">{{ $row['text'][$language] ?? '' }}</textarea>
                    </label>
                @endforeach
                <label class="block text-sm font-semibold">{{ __('admin.procedure.merge_key') }}
                    <input aria-describedby="procedure-errors" aria-invalid="{{ $errors->has('advice_items.'.($sectionKey).'.'.($index).'.merge_key') ? 'true' : 'false' }}" name="advice_items[{{ $sectionKey }}][{{ $index }}][merge_key]" value="{{ $row['merge_key'] ?? '' }}" list="merge_keys" pattern="[a-z0-9_.]+" maxlength="255" class="mt-2 w-full rounded-md border-slate-300 focus:ring-brand-primary">
                </label>
            </div>
        @endforeach
    </fieldset>
    <div x-show="ready" style="display: none" class="space-y-5">
        <div class="flex gap-2">
            @foreach (['et', 'en'] as $language)
                <button type="button" @click="tab = '{{ $language }}'" :aria-pressed="tab === '{{ $language }}'" :class="tab === '{{ $language }}' ? 'bg-brand-primary text-white' : 'bg-slate-100 text-slate-700'" class="min-h-11 rounded-md px-4 text-sm font-semibold focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('admin.procedure.'.$language) }}</button>
            @endforeach
        </div>
        <template x-for="(row, index) in rows" :key="row.id">
            <div class="space-y-3 border-b border-slate-100 pb-5">
                @foreach (['et', 'en'] as $language)
                    <label x-show="tab === null || tab === '{{ $language }}'" class="block text-sm font-semibold">{{ __('admin.procedure.text') }} — {{ __('admin.procedure.'.$language) }}
                        <textarea :name="'advice_items[{{ $sectionKey }}][' + index + '][text][{{ $language }}]'" aria-describedby="procedure-errors" :aria-invalid="errors.includes('advice_items.{{ $sectionKey }}.' + index + '.text.{{ $language }}') ? 'true' : 'false'" x-model="row.text.{{ $language }}" lang="{{ $language }}" rows="3" maxlength="10000" class="mt-2 w-full rounded-md border-slate-300 focus:ring-brand-primary"></textarea>
                    </label>
                @endforeach
                <label class="block text-sm font-semibold">{{ __('admin.procedure.merge_key') }}
                    <input :name="'advice_items[{{ $sectionKey }}][' + index + '][merge_key]'" aria-describedby="procedure-errors" :aria-invalid="errors.includes('advice_items.{{ $sectionKey }}.' + index + '.merge_key') ? 'true' : 'false'" x-model="row.merge_key" list="merge_keys" pattern="[a-z0-9_.]+" maxlength="255" class="mt-2 w-full rounded-md border-slate-300 focus:ring-brand-primary">
                </label>
                <div class="flex flex-wrap gap-3">
                    <button type="button" @click="move(index, -1)" :disabled="index === 0" class="min-h-11 rounded px-3 text-sm font-semibold text-brand-primary hover:bg-blue-50 focus-visible:ring-2 focus-visible:ring-brand-primary disabled:opacity-50">{{ __('admin.procedure.up') }}</button>
                    <button type="button" @click="move(index, 1)" :disabled="index === rows.length - 1" class="min-h-11 rounded px-3 text-sm font-semibold text-brand-primary hover:bg-blue-50 focus-visible:ring-2 focus-visible:ring-brand-primary disabled:opacity-50">{{ __('admin.procedure.down') }}</button>
                    <button type="button" @click="rows.splice(index, 1)" class="min-h-11 rounded px-3 text-sm font-semibold text-red-700 hover:bg-red-50 focus-visible:ring-2 focus-visible:ring-red-700">{{ __('admin.procedure.remove') }}</button>
                </div>
            </div>
        </template>
        <button type="button" @click="rows.push({ id: nextId++, text: { et: '', en: '' }, merge_key: '' })" class="min-h-11 rounded-md border border-slate-300 px-4 text-sm font-semibold hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('admin.procedure.add') }}</button>
    </div>
</section>
