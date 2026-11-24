{{-- Start with no footer: mPDF measures named headers before selecting the cover. --}}
@page { size: {{ config('leaflet.pdf.format')[0] }}mm {{ config('leaflet.pdf.format')[1] }}mm; odd-header-name: html_primary; even-header-name: html_primary; odd-footer-name: _blank; even-footer-name: _blank; }
@page cover { odd-footer-name: _blank; even-footer-name: _blank; }
@page general { odd-footer-name: html_running; even-footer-name: html_running; }
@foreach ($doc->sections as $index => $section)
    @page section-{{ $index }} { odd-header-name: html_section-{{ $index }}; even-header-name: html_section-{{ $index }}; odd-footer-name: html_running; even-footer-name: html_running; }
@endforeach
body { color: {{ $settings->get('brand.colors')['ink'] }}; }
.footer { color: {{ config('leaflet.colors.muted') }}; border-top: 0.2mm solid {{ config('leaflet.colors.hairline') }}; }
.draft-notice { color: {{ config('leaflet.colors.warn.dark') }}; background-color: {{ config('leaflet.colors.warn.tint') }}; }
