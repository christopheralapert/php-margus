@inject('icons', 'App\Services\Leaflet\PdfIcons')
@inject('advice', 'App\Services\Leaflet\PdfAdvice')
@php($topics = $advice->topics($procedure, $doc->locale))
@if ($topics !== [])
    @foreach (array_chunk($topics, 2) as $tiles)
        <div class="topic-group keep mb-s6">
            @if ($loop->first)
                <h2 class="mb-s2" style="color: {{ $theme->dark() }};">{{ __('pdf.everyday_life', [], $doc->locale) }}</h2>
            @endif
            <table width="100%" autosize="1" class="topic-row"><tr>
                @foreach ($tiles as $tile)
                    <td width="{{ count($tiles) === 1 ? '100%' : '50%' }}" colspan="{{ count($tiles) === 1 ? 2 : 1 }}" class="topic-cell {{ count($tiles) === 1 ? 'topic-full' : ($loop->first ? 'topic-left' : 'topic-right') }}">
                        {{-- mPDF discards block padding/background inside cells. A nested
                             table and text-free SVG caps retain the rounded panel. --}}
                        <table width="100%" autosize="1" class="topic-panel">
                        <tr><td class="topic-cap">{!! $icons->tileCap(false, count($tiles) === 1 ? 120 : 57) !!}</td></tr>
                        <tr><td class="topic-body">
                        <table width="100%" autosize="1" class="box-heading mb-s3"><tr>
                            <td width="8mm" class="heading-icon">{!! $icons->image($tile['icon'], $theme->dark(), 5) !!}</td>
                            <td><h2 style="color: {{ $theme->dark() }};">{{ __('enums.advice_section.'.$tile['section'], [], $doc->locale) }}</h2></td>
                        </tr></table>
                        @foreach ($tile['paragraphs'] as $paragraph)
                            <p class="topic-copy">{{ $paragraph }}</p>
                        @endforeach
                        </td></tr>
                        <tr><td class="topic-cap">{!! $icons->tileCap(true, count($tiles) === 1 ? 120 : 57) !!}</td></tr>
                        </table>
                    </td>
                @endforeach
            </tr></table>
        </div>
    @endforeach
@endif
