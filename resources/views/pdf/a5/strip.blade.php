{{-- Named headers repeat on automatic continuation pages as well as section starts. --}}
@php
    $bleed = $printMode ? config('leaflet.print.bleed') : 0;
    $width = config('leaflet.pdf.format')[0] + 2 * $bleed;
    $left = $printMode ? (config('leaflet.print.sheet')[0] - config('leaflet.pdf.format')[0]) / 2 - $bleed : 0;
    $top = $printMode ? (config('leaflet.print.sheet')[1] - config('leaflet.pdf.format')[1]) / 2 - $bleed : 0;
@endphp
<div style="position: absolute; top: {{ $top }}mm; left: {{ $left }}mm; width: {{ $width }}mm; height: {{ 3 + $bleed }}mm; background-color: {{ $color }};"></div>
