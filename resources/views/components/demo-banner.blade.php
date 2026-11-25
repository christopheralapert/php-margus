@if (config('app.demo_mode') && config('app.demo_banner'))
    <div role="note" class="w-full border-b border-amber-300 bg-amber-100 px-4 py-3 text-center text-sm text-amber-900">
        {{ __('demo.banner') }}
    </div>
@endif
