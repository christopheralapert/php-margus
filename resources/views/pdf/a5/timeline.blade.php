@inject('icons', 'App\Services\Leaflet\PdfIcons')
@if ($procedure->timelineSteps->isNotEmpty())
    <div class="mb-s6">
        <div class="keep">
            <h2 style="color: {{ $theme->dark() }};">{{ __('pdf.recovery_timeline', [], $doc->locale) }}</h2>
            <div class="timeline-ruler">{!! $icons->timelineRuler($theme) !!}</div>
            <table width="120mm" autosize="1" class="timeline-markers mb-s3"><tr>
                @foreach ($procedure->timelineSteps as $step)
                    <td width="25%" class="label" style="color: {{ $theme->dark() }};">{{ $step->getTranslation('marker', $doc->locale) }}</td>
                @endforeach
            </tr></table>
            @include('pdf.a5.timeline-step', ['step' => $procedure->timelineSteps->first(), 'number' => 1])
        </div>
        @foreach ($procedure->timelineSteps->skip(1) as $step)
            @include('pdf.a5.timeline-step', ['number' => $loop->iteration + 1])
        @endforeach
    </div>
@endif
