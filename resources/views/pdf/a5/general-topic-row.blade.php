<table width="100%" autosize="1" class="general-topic-row{{ $first ? ' general-topic-first' : '' }}"><tr>
    <td width="8mm" class="general-topic-icon">{!! $icons->image($topic['icon'], $theme->dark(), 5) !!}</td>
    <td><span class="strong">{{ __('enums.advice_section.'.$topic['section'], [], $doc->locale) }}</span> — {{ implode(' ', $topic['paragraphs']) }}</td>
</tr></table>
