@inject('icons', 'App\Services\Leaflet\PdfIcons')
@inject('advice', 'App\Services\Leaflet\PdfAdvice')
@inject('settings', 'App\Support\Settings')
<div class="redflag-box keep mb-s6">
    <div class="redflag-band">
        <table width="100%" autosize="1" class="box-heading"><tr>
            <td width="10mm">{!! $icons->image('warning-triangle', '#FFFFFF', 7) !!}</td>
            <td><h2 style="color: #FFFFFF; font-family: nunito; font-weight: bold; font-size: 16pt; line-height: 20pt;">{{ mb_strtoupper(__('pdf.red_flags', [], $doc->locale), 'UTF-8') }}</h2></td>
        </tr></table>
    </div>
    <div class="redflag-body">
        <table width="100%" autosize="1" class="redflag-item">
        @php($previousProcedureId = null)
        @foreach ($advice->redFlags($doc) as $flag)
            @if (count($doc->sections) > 1 && $flag['procedure'] && $previousProcedureId !== $flag['procedure']->id)
                <tr>
                    <td colspan="2" class="redflag-group">{!! $icons->dot($flag['theme']->base(), 3) !!} {{ $flag['procedure']->getTranslation('name', $doc->locale) }}</td>
                </tr>
            @endif
            @php($previousProcedureId = $flag['procedure']?->id)
            <tr>
                <td width="6mm" class="redflag-dot">{!! $icons->dot('#D93B3B', 2.5) !!}</td>
                <td width="104mm">
                    {{ $flag['item']->getTranslation('text', $doc->locale) }}
                </td>
            </tr>
        @endforeach
        </table>
        <table width="100%" autosize="1" class="redflag-contacts mt-s4"><tr>
            <td width="75mm" class="redflag-clinic">
                <table width="100%" autosize="1" class="redflag-clinic-lines">
                    <tr><td class="strong contact-gap">{{ __('pdf.call_clinic', [], $doc->locale) }}</td></tr>
                    <tr><td class="phone-xl contact-gap">{{ $settings->get('clinic.phone') }}</td></tr>
                    <tr><td>{{ __('pdf.after_hours', ['phone' => $settings->get('clinic.after_hours_phone')], $doc->locale) }}</td></tr>
                </table>
            </td>
            <td width="32mm">
                <table width="32mm" autosize="1" class="emergency-pill">
                    <tr><td class="emergency-cap">{!! $icons->emergencyCap(false) !!}</td></tr>
                    <tr><td class="emergency-body">
                        <div class="label">{{ __('pdf.emergency', [], $doc->locale) }}</div>
                        <div class="emergency-xxl">{{ $settings->get('clinic.emergency') }}</div>
                    </td></tr>
                    <tr><td class="emergency-cap">{!! $icons->emergencyCap(true) !!}</td></tr>
                </table>
            </td>
        </tr></table>
    </div>
</div>
