@inject('renderer', 'App\Services\Leaflet\PdfRenderer')
@foreach ([\App\Enums\AdviceSection::Do, \App\Enums\AdviceSection::Dont] as $section)
    @php
        $items = $procedure->adviceItems->where('section', $section)->filter(fn ($item) => trim($item->getTranslation('text', $doc->locale)) !== '')->values();
        $colors = config('leaflet.colors.'.$section->value);
        $icon = $section === \App\Enums\AdviceSection::Do ? 'check' : 'cross';
        $columns = $items->count() >= 6 ? 2 : 1;
        $rowCount = (int) ceil($items->count() / $columns);
    @endphp
    @if ($items->isNotEmpty())
        @php
            $cardData = compact('doc', 'items', 'colors', 'section', 'icon', 'columns', 'rowCount');
            $card = view('pdf.a5.card', [...$cardData, 'keepCard' => true])->render();
        @endphp
        {!! $renderer->blockSpansPages($card) ? view('pdf.a5.card', [...$cardData, 'keepCard' => false])->render() : $card !!}
    @endif
@endforeach
