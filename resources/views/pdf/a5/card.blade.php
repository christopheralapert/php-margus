@inject('icons', 'App\Services\Leaflet\PdfIcons')
<div class="advice-card{{ $keepCard ? ' keep' : '' }} mb-s6" style="background-color: {{ $colors['tint'] }}; border-left: 1.5mm solid {{ $colors['base'] }};">
    <div class="keep">
        <table width="100%" autosize="1" class="box-heading mb-s3"><tr>
            <td width="9mm">{!! $icons->badge($icon, $colors['base']) !!}</td>
            <td><h2 style="color: {{ $colors['dark'] }};">{{ __('pdf.cards.'.$section->value, [], $doc->locale) }}</h2></td>
        </tr></table>
        @for ($row = 0; $row < min(2, $rowCount); $row++)
            @include('pdf.a5.card-row')
        @endfor
    </div>
    @for ($row = 2; $row < $rowCount; $row++)
        @include('pdf.a5.card-row')
    @endfor
</div>
