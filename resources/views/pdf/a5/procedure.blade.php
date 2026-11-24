@inject('icons', 'App\Services\Leaflet\PdfIcons')
@php
    $labelChannels = sscanf($theme->on(), '#%02x%02x%02x');
    $baseChannels = sscanf($theme->base(), '#%02x%02x%02x');
    $labelColor = sprintf('#%02X%02X%02X', ...array_map(fn ($on, $base) => (int) round($on * 0.85 + $base * 0.15), $labelChannels, $baseChannels));
@endphp
<div class="section-band keep mb-s6" style="background-color: {{ $theme->base() }};">
    <table width="100%" autosize="1" class="procedure-header"><tr>
        <td width="16mm">{!! $icons->category($procedure->category->slug, $theme->base()) !!}</td>
        <td>
            <div class="label mb-s1" style="color: {{ $labelColor }};">{{ mb_strtoupper($procedure->category->getTranslation('name', $doc->locale), 'UTF-8') }}</div>
            <h1 style="color: {{ $theme->on() }};">{{ $procedure->getTranslation('name', $doc->locale) }}</h1>
        </td>
    </tr></table>
</div>
<p class="mb-s6">{{ $procedure->getTranslation('summary', $doc->locale) }}</p>
@include('pdf.a5.timeline')
@unless ($categoryProof ?? false)
    @include('pdf.a5.content')
@endunless
