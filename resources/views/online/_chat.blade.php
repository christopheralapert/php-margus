@php
    $chatOptions = [
        'endpoint' => route('chat.send'),
        'procedures' => array_values(array_unique(array_map(fn ($section) => $section['procedure']->slug, $doc->sections))),
        'maxChars' => (int) config('chat.max_chars'),
        'validation' => __('chat.validation'),
        'networkError' => __('chat.network_error'),
    ];
    $clinicTel = str_replace(' ', '', $settings->get('clinic.phone'));
    $emergency = $settings->get('clinic.emergency');
@endphp
<noscript><p class="mt-10 rounded-xl bg-brand-soft p-5">{{ __('chat.needs_javascript') }}</p></noscript>
<section data-chat x-data="leafletChat({{ Js::from($chatOptions) }})" x-cloak x-show="true" aria-labelledby="chat-title" class="mt-12 border-t border-slate-200 pt-10">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h2 id="chat-title" class="text-2xl font-semibold">{{ __('chat.title') }}</h2>
        <a href="tel:{{ $clinicTel }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-brand-primary px-5 py-3 font-semibold text-white hover:bg-blue-800 focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">{{ __('wizard.phone') }} · {{ $settings->get('clinic.phone') }}</a>
    </div>
    <p class="mt-4 text-sm font-semibold text-red-900">{{ __('chat.emergency_reminder', ['emergency' => $emergency]) }}</p>
    <p class="mt-3 max-w-prose text-sm leading-relaxed text-slate-600">{{ __('chat.memory_note') }}</p>

    <ol aria-live="polite" aria-relevant="additions" aria-label="{{ __('chat.messages') }}" class="mt-6 space-y-4">
        <template x-for="(item, index) in messages" :key="index">
            <li class="max-w-prose break-words rounded-xl border p-5" :class="item.redFlag ? 'border-red-300 bg-red-50 text-red-900' : (item.fallback ? 'border-slate-300 bg-slate-50 text-slate-800' : (item.role === 'user' ? 'ml-auto border-blue-200 bg-blue-50 text-blue-950' : 'border-slate-200 bg-white text-brand-ink'))">
                <p class="mb-2 text-sm font-semibold" x-text="item.role === 'user' ? {{ Js::from(__('chat.you')) }} : {{ Js::from(__('chat.assistant')) }}"></p>
                <p class="whitespace-pre-wrap leading-relaxed" x-text="item.content"></p>
                <div x-show="item.redFlag" class="mt-3 flex flex-wrap gap-4 font-semibold">
                    <a href="tel:{{ $clinicTel }}" class="inline-flex min-h-11 items-center rounded underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-red-900">{{ __('wizard.phone') }}</a>
                    <a href="tel:{{ $emergency }}" class="inline-flex min-h-11 items-center rounded underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-red-900">{{ __('wizard.emergency') }} {{ $emergency }}</a>
                </div>
            </li>
        </template>
    </ol>
    <p x-show="loading" role="status" class="mt-4 text-sm text-slate-600">{{ __('chat.loading') }}</p>
    <p id="chat-error" x-show="error" x-text="error" role="alert" class="mt-4 rounded-xl border border-red-300 bg-red-50 p-4 text-red-900"></p>

    <form @submit.prevent="send" class="mt-6" :aria-busy="loading">
        <p id="chat-disclaimer" class="mb-5 max-w-prose rounded-xl bg-brand-soft p-5 leading-relaxed">{{ __('chat.disclaimer', ['phone' => $settings->get('clinic.phone'), 'emergency' => $emergency]) }}</p>
        <label for="chat-message" class="mb-2 block font-semibold">{{ __('chat.message') }}</label>
        <textarea id="chat-message" x-ref="input" x-model="message" :readonly="loading" maxlength="{{ config('chat.max_chars') }}" rows="3" required aria-describedby="chat-disclaimer chat-count chat-error" :aria-invalid="error ? 'true' : 'false'" autocomplete="off" class="w-full rounded-xl border-slate-400 focus:border-brand-primary focus:ring-brand-primary"></textarea>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <p id="chat-count" class="text-sm tabular-nums text-slate-600" x-text="{{ Js::from(__('chat.character_count', ['max' => config('chat.max_chars')])) }}.replace(':count', characterCount)"></p>
            <button type="submit" :disabled="loading || !message.trim() || characterCount > {{ (int) config('chat.max_chars') }}" class="min-h-12 rounded-xl bg-brand-primary px-7 py-3 font-semibold text-white hover:bg-blue-800 focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-600">{{ __('chat.send') }}</button>
        </div>
    </form>
</section>
