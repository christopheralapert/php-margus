@inject('icons', 'App\Services\Leaflet\PdfIcons')
@inject('illustrations', 'App\Services\Leaflet\PdfIllustrations')
@php
    $multi = count($doc->sections) > 1;
    $primary = new \App\Services\Leaflet\ThemeColor($settings->get('brand.colors')['primary']);
    $coverTheme = $multi ? $primary : ($doc->sections[0]['theme'] ?? $primary);
    $bleed = $printMode ? config('leaflet.print.bleed') : 0;
    $offsetX = $printMode ? (config('leaflet.print.sheet')[0] - 148) / 2 : 0;
    $offsetY = $printMode ? (config('leaflet.print.sheet')[1] - 210) / 2 : 0;
    $contentLeft = $printMode ? config('leaflet.print.margin_inner') : config('leaflet.pdf.margin_left');
    // A5 trim coordinates: logo ends at 23, bottom band starts at 198.
    // Text budget: 34pt title + 4mm gap + (20pt + 2mm) per procedure;
    // optional details: 6mm gap + 17pt; draft: 6mm gap + 11pt + 4mm padding.
    $pt = 25.4 / 72;
    $textHeight = 34 * $pt + 4 + count($doc->sections) * (20 * $pt + 2)
        + (($doc->firstName || $doc->date) ? 6 + 17 * $pt : 0)
        + ($doc->hasDraft() ? 6 + 11 * $pt + 4 : 0);
    $artHeight = 70;
    $artGap = 8;
    $artTop = round((23 + 198 - $artHeight - $artGap - $textHeight) / 2, 2);
    $textTop = $artTop + $artHeight + $artGap;
@endphp
{{-- Keep a normal-flow cover block so mPDF preserves the following pagebreak. --}}
<div class="cover">&nbsp;</div>
{{-- Absolute decorations are top-level siblings: mPDF cannot position nested divs reliably. --}}
<div style="position: absolute; top: {{ $offsetY + $artTop }}mm; left: {{ $offsetX + $contentLeft }}mm; width: 120mm; height: {{ $artHeight }}mm; border-radius: 28mm; background-color: {{ $coverTheme->tint() }};"></div>
<div class="cover-art" style="top: {{ $offsetY + $artTop + ($artHeight - ($multi ? 38 : 67.5)) / 2 }}mm; left: {{ $offsetX + $contentLeft }}mm;">
    @if ($multi)
        <table width="100%" autosize="1" class="cover-illustrations"><tr>
            @foreach (array_slice($doc->sections, 0, 3) as $section)
                <td align="center" width="{{ 100 / min(3, count($doc->sections)) }}%">
                    @php($art = $section['procedure']->cover_illustration_path ?: $section['procedure']->illustration_path)
                    @if ($art && is_file(public_path($art)))
                        <img src="{{ strtolower(pathinfo($art, PATHINFO_EXTENSION)) === 'svg' ? $illustrations->circleDataUri($art, $section['theme']) : public_path($art) }}" width="38mm" height="38mm" alt="">
                    @endif
                </td>
            @endforeach
        </tr></table>
    @else
        @php($art = isset($doc->sections[0]) ? ($doc->sections[0]['procedure']->cover_illustration_path ?: $doc->sections[0]['procedure']->illustration_path) : null)
        @if ($art && is_file(public_path($art)))
            <img src="{{ public_path($art) }}" width="90mm" alt="">
        @endif
    @endif
</div>
<div class="cover-band" style="top: {{ $offsetY + 198 }}mm; left: {{ $offsetX - $bleed }}mm; width: {{ 148 + 2 * $bleed }}mm; height: {{ 12 + $bleed }}mm; background-color: {{ $primary->base() }};">
    <table width="120mm" autosize="1" style="margin-left: {{ $contentLeft + $bleed }}mm;"><tr>
        <td style="color: #FFFFFF; font-family: nunito; font-size: 11pt;">{{ $settings->localized('clinic.name', $doc->locale) }}</td>
        <td align="right" style="color: #FFFFFF; font-family: nunito; font-size: 11pt;">{{ $settings->get('clinic.phone') }}</td>
    </tr></table>
</div>
<div class="cover-logo" style="top: {{ $offsetY + 14 }}mm; left: {{ $offsetX + $contentLeft }}mm;">
        @if (is_file(public_path($settings->get('brand.logo_path'))))
            <img src="{{ public_path($settings->get('brand.logo_path')) }}" width="32mm" alt="{{ $settings->localized('clinic.name', $doc->locale) }}">
        @endif
</div>
<div class="cover-copy" style="top: {{ $offsetY + $textTop }}mm; left: {{ $offsetX + $contentLeft }}mm;">
    <div class="cover-title mb-s4">{{ __('pdf.title', [], $doc->locale) }}</div>
    <table width="100%" autosize="1" class="cover-procedures">
        @foreach ($doc->sections as ['procedure' => $procedure, 'theme' => $theme])
            <tr><td width="7mm">{!! $icons->dot($theme->base()) !!}</td><td>{{ $procedure->getTranslation('name', $doc->locale) }}</td></tr>
        @endforeach
    </table>
    @if ($doc->firstName || $doc->date)
        <p class="cover-sub mt-s6">
            @if ($doc->firstName)
                {{ __('pdf.recipient', ['name' => $doc->firstName], $doc->locale) }}
            @endif
            @if ($doc->firstName && $doc->date)
                ·
            @endif
            @if ($doc->date)
                {{ $doc->formattedDate() }}
            @endif
        </p>
    @endif
    @if ($doc->hasDraft())
        <div class="draft-notice mt-s6">{{ __('pdf.draft_notice', [], $doc->locale) }}</div>
    @endif
</div>
