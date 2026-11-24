@inject('icons', 'App\Services\Leaflet\PdfIcons')
@inject('settings', 'App\Support\Settings')
@php($secondary = new \App\Services\Leaflet\ThemeColor($settings->get('brand.colors')['secondary']))
@foreach ($procedures as $followupProcedure)
    @if (trim($followupProcedure->getTranslation('follow_up', $doc->locale)) !== '' || $doc->date)
        <div class="followup-box keep mb-s6" style="background-color: {{ $secondary->tint() }};">
            <table width="100%" autosize="1" class="box-heading mb-s3"><tr>
                <td width="8mm">{!! $icons->image('calendar', $secondary->dark(), 5) !!}</td>
                <td><h2 style="color: {{ $secondary->dark() }};">{{ __('pdf.follow_up', [], $doc->locale) }}</h2></td>
            </tr></table>
            @if (count($procedures) > 1)
                <p class="strong">{{ $followupProcedure->getTranslation('name', $doc->locale) }}</p>
            @endif
            <p class="{{ $doc->date ? '' : 'box-last' }}">{{ $followupProcedure->getTranslation('follow_up', $doc->locale) }}</p>
            @if ($doc->date)
                <p class="box-last">{{ __('pdf.procedure_date', ['date' => $doc->formattedDate()], $doc->locale) }}</p>
            @endif
        </div>
    @endif
@endforeach
