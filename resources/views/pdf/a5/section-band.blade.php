@inject('icons', 'App\Services\Leaflet\PdfIcons')
@php
    $labelChannels = sscanf($theme->on(), '#%02x%02x%02x');
    $baseChannels = sscanf($theme->base(), '#%02x%02x%02x');
    $labelColor = sprintf('#%02X%02X%02X', ...array_map(fn ($on, $base) => (int) round($on * 0.85 + $base * 0.15), $labelChannels, $baseChannels));
@endphp
<div class="section-band keep mb-s6" style="background-color: {{ $theme->base() }};">
    <table width="100%" autosize="1" class="procedure-header"><tr>
        <td width="16mm">{!! $bandIcon !!}</td>
        <td>
            <div class="label mb-s1" style="color: {{ $labelColor }};">{{ mb_strtoupper($bandLabel, 'UTF-8') }}</div>
            <h1 style="color: {{ $theme->on() }};">{{ $bandTitle }}</h1>
        </td>
    </tr></table>
</div>
