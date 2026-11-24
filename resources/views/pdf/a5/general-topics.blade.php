@inject('advice', 'App\Services\Leaflet\PdfAdvice')
@php($topics = $advice->topics(null, $doc->locale, $doc))
@if ($topics !== [])
    <div class="general-topics mb-s6">
        <div class="keep">
            <h2 style="color: {{ $theme->dark() }};">{{ __('pdf.everyday_life', [], $doc->locale) }}</h2>
            @foreach (array_slice($topics, 0, 2) as $topic)
                @include('pdf.a5.general-topic-row', ['first' => $loop->first])
            @endforeach
        </div>
        @foreach (array_slice($topics, 2) as $topic)
            @include('pdf.a5.general-topic-row', ['first' => false])
        @endforeach
    </div>
@endif
