@inject('icons', 'App\Services\Leaflet\PdfIcons')
@include('pdf.a5.section-band', [
    'bandIcon' => $icons->category($procedure->category->slug, $theme->base()),
    'bandLabel' => $procedure->category->getTranslation('name', $doc->locale),
    'bandTitle' => $procedure->getTranslation('name', $doc->locale),
])
<p class="mb-s6">{{ $procedure->getTranslation('summary', $doc->locale) }}</p>
@include('pdf.a5.timeline')
@unless ($categoryProof ?? false)
    @include('pdf.a5.cards')
    @include('pdf.a5.topics')
@endunless
