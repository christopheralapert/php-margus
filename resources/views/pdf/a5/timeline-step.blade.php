<table width="120mm" autosize="1" class="timeline-step"><tr>
    <td width="26mm" class="timeline-phase">
        <div class="timeline-number">{!! $icons->timelineCircle($number, $theme) !!}</div>
        <div class="label" style="color: {{ $theme->dark() }};">{{ preg_replace('/ (?=\d)|(?<=\d\.) /u', "\u{00A0}", mb_strtoupper($step->getTranslation('marker', $doc->locale), 'UTF-8')) }}</div>
    </td>
    <td class="timeline-copy" style="border-left: 0.8mm solid {{ $theme->base() }};">
        <div class="strong">{{ $step->getTranslation('title', $doc->locale) }}</div>
        <div>{{ $step->getTranslation('body', $doc->locale) }}</div>
    </td>
</tr></table>
