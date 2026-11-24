<!DOCTYPE html>
<html lang="{{ $doc->locale }}">
<head>
    <meta charset="utf-8">
</head>
<body>
    <htmlpageheader name="primary">
        @include('pdf.a5.strip', ['color' => $settings->get('brand.colors')['primary']])
    </htmlpageheader>
    @foreach ($doc->sections as $index => $section)
        <htmlpageheader name="section-{{ $index }}">
            @include('pdf.a5.strip', ['color' => $section['theme']->base()])
        </htmlpageheader>
    @endforeach
    <htmlpagefooter name="running">@include('pdf.a5.footer')</htmlpagefooter>

    <div class="cover">
        @if (is_file(public_path($settings->get('brand.logo_path'))))
            <img src="{{ public_path($settings->get('brand.logo_path')) }}" width="32mm" alt="{{ $settings->localized('clinic.name', $doc->locale) }}">
        @endif
        <div class="cover-title mt-s12 mb-s6">{{ __('pdf.title', [], $doc->locale) }}</div>
        @if ($doc->hasDraft())
            <p class="draft-notice">{{ __('pdf.draft_notice', [], $doc->locale) }}</p>
        @endif
        @foreach ($doc->sections as ['procedure' => $procedure, 'theme' => $theme])
            <h2 style="color: {{ $theme->dark() }}">{{ $procedure->getTranslation('name', $doc->locale) }}</h2>
        @endforeach
        <div class="cover-sub mt-s6">@include('wizard.personal-details')</div>
    </div>

    @foreach ($doc->sections as $index => $section)
        <pagebreak page-selector="section-{{ $index }}" />
        @include('pdf.a5.content', $section)
    @endforeach
</body>
</html>
