@extends('layouts.patient')
@inject('settings', 'App\Support\Settings')
@inject('advice', 'App\Services\Leaflet\PdfAdvice')

@section('content')
    <x-wizard-progress :step="3" />
    <h1 class="text-3xl font-semibold sm:text-4xl">{{ $doc->title() }}</h1>
    @include('wizard.personal-details')
    @if ($doc->hasDraft())
        <p class="mt-6 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">{{ __('wizard.draft') }}</p>
    @endif
    <div class="mt-8 flex flex-wrap items-center gap-5">
        <form method="POST" action="{{ route('leaflet.pdf') }}">
            @csrf
            <button type="submit" class="min-h-14 rounded-xl bg-brand-primary px-8 py-4 text-lg font-semibold text-white hover:bg-blue-800 focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">{{ __('wizard.download') }}</button>
        </form>
        <a href="{{ route('wizard.details') }}" class="inline-flex min-h-11 items-center rounded font-semibold text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('wizard.edit') }}</a>
        @auth
            @if (auth()->user()->hasVerifiedEmail())
                <form method="POST" action="{{ route('account.leaflets.store') }}">
                    @csrf
                    <button type="submit" class="min-h-11 rounded-xl border border-brand-primary px-5 py-3 font-semibold text-brand-primary hover:bg-brand-soft focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2">{{ __('account.save') }}</button>
                </form>
            @else
                <a href="{{ route('verification.notice') }}" class="inline-flex min-h-11 items-center rounded text-sm text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('account.verify_to_save') }}</a>
            @endif
        @else
            <a href="{{ route('login', ['save_leaflet' => '1']) }}" class="inline-flex min-h-11 items-center rounded text-sm text-brand-primary underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('account.login_to_save') }}</a>
        @endauth
    </div>
    <div class="mt-10 space-y-10">
        @if ($doc->general() !== [])
            <section data-general-advice class="overflow-hidden rounded-xl border border-slate-200">
                <header class="bg-brand-primary p-6 text-white sm:p-8">
                    <h2 class="text-2xl font-semibold">{{ __('pdf.general_advice', [], $doc->locale) }}</h2>
                    <ul class="mt-3 flex flex-wrap gap-3">
                        @foreach ($doc->sections as $covered)
                            <li class="flex items-center gap-2 font-semibold"><span aria-hidden="true" class="h-3 w-3 rounded-full" style="background-color: {{ $covered['theme']->base() }}"></span>{{ $covered['procedure']->getTranslation('name', $doc->locale) }}</li>
                        @endforeach
                    </ul>
                </header>
                <div class="space-y-8 p-6 sm:p-8">
                    @foreach ($doc->general() as $section => $items)
                        @php($paragraphs = $section === 'pain' ? (collect($advice->topics(null, $doc->locale, $doc))->firstWhere('section', 'pain')['paragraphs'] ?? []) : $items->map(fn ($item) => $item->getTranslation('text', $doc->locale)))
                        <div>
                            <h3 class="text-xl font-semibold">{{ __('enums.advice_section.'.$section, [], $doc->locale) }}</h3>
                            <ul class="mt-3 list-disc space-y-2 pl-5 leading-relaxed">
                                @foreach ($paragraphs as $paragraph)<li class="whitespace-pre-line">{{ $paragraph }}</li>@endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
        @foreach ($doc->sections as ['procedure' => $procedure, 'theme' => $theme])
            <section class="overflow-hidden rounded-xl border border-slate-200">
                <header class="p-6 sm:p-8" style="background-color: {{ $theme->tint() }}; color: {{ $theme->dark() }}">
                    <h2 class="break-words text-2xl font-semibold">{{ $procedure->getTranslation('name', $doc->locale) }}</h2>
                    <p class="mt-3 leading-relaxed">{{ $procedure->getTranslation('summary', $doc->locale) }}</p>
                </header>
                <div class="space-y-8 p-6 sm:p-8">
                    @if ($procedure->timelineSteps->isNotEmpty())
                        <div>
                            <h3 class="text-xl font-semibold">{{ __('wizard.timeline') }}</h3>
                            <ol class="mt-4 space-y-5">
                                @foreach ($procedure->timelineSteps as $step)
                                    <li>
                                        <p class="font-semibold" style="color: {{ $theme->dark() }}">{{ $step->getTranslation('marker', $doc->locale) }} · {{ $step->getTranslation('title', $doc->locale) }}</p>
                                        <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $step->getTranslation('body', $doc->locale) }}</p>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                    @foreach (\App\Enums\AdviceSection::cases() as $adviceSection)
                        @php($items = $doc->advice($procedure, $adviceSection))
                        @if ($items->isNotEmpty() || $adviceSection === \App\Enums\AdviceSection::RedFlag)
                            @php($paragraphs = $adviceSection === \App\Enums\AdviceSection::Pain ? (collect($advice->topics($procedure, $doc->locale, $doc))->firstWhere('section', 'pain')['paragraphs'] ?? []) : $items->map(fn ($item) => $item->getTranslation('text', $doc->locale)))
                            <div @if ($adviceSection === \App\Enums\AdviceSection::RedFlag) data-red-flags class="rounded-xl border border-red-300 bg-red-50 p-5 text-red-900" @endif>
                                <h3 class="text-xl font-semibold">
                                    @if ($adviceSection === \App\Enums\AdviceSection::Do)<span aria-hidden="true" class="text-green-700">✓</span>@endif
                                    @if ($adviceSection === \App\Enums\AdviceSection::Dont)<span aria-hidden="true" class="text-red-700">✕</span>@endif
                                    {{ $adviceSection->label() }}
                                </h3>
                                <ul class="mt-3 list-disc space-y-2 pl-5 leading-relaxed">
                                    @foreach ($paragraphs as $paragraph)<li class="whitespace-pre-line">{{ $paragraph }}</li>@endforeach
                                </ul>
                                @if ($adviceSection === \App\Enums\AdviceSection::RedFlag) @include('wizard.contacts') @endif
                            </div>
                        @endif
                    @endforeach
                    @if ($procedure->getTranslation('follow_up', $doc->locale))
                        <div>
                            <h3 class="text-xl font-semibold">{{ __('wizard.follow_up') }}</h3>
                            <p class="mt-3 whitespace-pre-line leading-relaxed">{{ $procedure->getTranslation('follow_up', $doc->locale) }}</p>
                        </div>
                    @endif
                </div>
            </section>
        @endforeach
    </div>
@endsection
