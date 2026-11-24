@inject('icons', 'App\Services\Leaflet\PdfIcons')
@php($theme = new \App\Services\Leaflet\ThemeColor($settings->get('brand.colors')['primary']))
<div class="general-section">
@include('pdf.a5.section-band', [
    'bandIcon' => $icons->image('info', $theme->on(), 12),
    'bandLabel' => __('pdf.general_label', [], $doc->locale),
    'bandTitle' => __('pdf.general_advice', [], $doc->locale),
])
<div class="general-chips mb-s6">
    @foreach ($doc->sections as $covered)
        <p class="strong general-chip">{!! $icons->dot($covered['theme']->base(), 3) !!}&#160;{{ str_replace(' ', "\u{00A0}", $covered['procedure']->getTranslation('name', $doc->locale)) }}</p>
    @endforeach
</div>
@include('pdf.a5.cards')
@include('pdf.a5.general-topics')
</div>
