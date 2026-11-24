@inject('icons', 'App\Services\Leaflet\PdfIcons')
@inject('qrCodes', 'App\Services\Leaflet\QrCodeFactory')
@php
    $onlineUrl = (new \App\Services\Leaflet\OnlineLeafletUrl($doc))->onlineUrl();
    $displayUrl = strlen($onlineUrl) > 60 ? preg_replace('~^https?://~', '', $onlineUrl) : $onlineUrl;
    $primary = new \App\Services\Leaflet\ThemeColor($settings->get('brand.colors')['primary']);
    $contacts = [
        'phone' => $settings->get('clinic.phone'),
        'moon' => __('pdf.back_after_hours', ['phone' => $settings->get('clinic.after_hours_phone')], $doc->locale),
        'mail' => $settings->get('clinic.email'),
        'pin' => $settings->localized('clinic.address', $doc->locale),
        'clock' => $settings->localized('clinic.hours', $doc->locale),
    ];
    $bleed = $printMode ? config('leaflet.print.bleed') : 0;
    $offsetX = $printMode ? (config('leaflet.print.sheet')[0] - 148) / 2 : 0;
    $offsetY = $printMode ? (config('leaflet.print.sheet')[1] - 210) / 2 : 0;
@endphp
<div class="back-logo mb-s4">
    @if (is_file(public_path($settings->get('brand.logo_path'))))
        <img src="{{ public_path($settings->get('brand.logo_path')) }}" width="40mm" alt="{{ $settings->localized('clinic.name', $doc->locale) }}">
    @endif
</div>
<h2 class="back-name">{{ $settings->localized('clinic.name', $doc->locale) }}</h2>
<table width="100%" autosize="1" class="back-contacts">
    @foreach ($contacts as $icon => $text)
        <tr><td width="8mm">{!! $icons->image($icon, $primary->dark(), 5) !!}</td><td>{{ $text }}</td></tr>
    @endforeach
</table>
<table width="100%" autosize="1" class="back-qr mt-s6 mb-s6">
    <tr>
        <td width="32mm"><img src="{{ $qrCodes->dataUri($onlineUrl) }}" width="32mm" height="32mm" alt="{{ __('pdf.qr_caption', [], $doc->locale) }}"></td>
        <td class="back-qr-caption">
            <p>{{ __('pdf.qr_caption', [], $doc->locale) }}</p>
            <div class="back-small">{{ $displayUrl }}</div>
        </td>
    </tr>
</table>
<div class="back-emergency mb-s6">{{ __('pdf.back_emergency', [], $doc->locale) }}</div>
<div class="back-disclaimer mb-s6">{{ $settings->localized('leaflet.disclaimer', $doc->locale) }} {{ __('pdf.back_contact', [], $doc->locale) }}</div>
<p class="back-small">{{ __('pdf.created', ['date' => now()->format($doc->locale === 'et' ? 'd.m.Y' : 'j M Y'), 'version' => config('leaflet.content_version', 'v1')], $doc->locale) }}</p>
<div style="position: absolute; top: {{ $offsetY + 198 }}mm; left: {{ $offsetX - $bleed }}mm; width: {{ 148 + 2 * $bleed }}mm; height: {{ 12 + $bleed }}mm; background-color: #F3F7FC;"></div>
