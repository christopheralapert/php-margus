@props(['step'])
<nav aria-label="{{ __('wizard.progress', ['step' => $step]) }}" class="mb-8">
    <p class="text-sm font-semibold text-slate-600">{{ __('wizard.progress', ['step' => $step]) }}</p>
    <ol class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm">
        @foreach (__('wizard.steps') as $label)
            <li @if ($loop->iteration === $step) aria-current="step" @endif class="{{ $loop->iteration === $step ? 'font-semibold text-brand-primary' : 'text-slate-600' }}">{{ $loop->iteration }}. {{ $label }}</li>
        @endforeach
    </ol>
</nav>
