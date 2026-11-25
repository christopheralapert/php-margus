@inject('icons', 'App\Services\Leaflet\PdfIcons')
@inject('qr', 'App\Services\Leaflet\QrCodeFactory')
@php
    $url = (new \App\Services\Leaflet\OnlineLeafletUrl($doc))->onlineUrl();
    $displayUrl = str_replace('/', '/&#8203;', e(preg_replace('~^https?://~', '', $url)));
    $t = fn (string $key, array $args = []) => __($key, $args, $doc->locale);
    $primary = new \App\Services\Leaflet\ThemeColor($settings->get('brand.colors')['primary']);
    $theme = count($doc->sections) === 1 ? $doc->sections[0]['theme'] : $primary;
    $groups = collect($blocks)->groupBy('group');
@endphp
@if ($kind === 'cover')
    <div class="cover-logo">
        @if (is_file(public_path($settings->get('brand.logo_path'))))
            <img src="{{ public_path($settings->get('brand.logo_path')) }}" width="32mm" alt="">
        @endif
    </div>
    @php
        $art = $doc->sections[0]['procedure']->cover_illustration_path ?: $doc->sections[0]['procedure']->illustration_path;
    @endphp
    @if ($artWidth && $art && is_file(public_path($art)))
        <div class="cover-art"><img src="{{ public_path($art) }}" width="{{ $artWidth }}mm" alt=""></div>
    @endif
    <h1>{{ $t('pdf.title') }}</h1>
    @foreach ($doc->sections as ['procedure' => $procedure, 'theme' => $procedureTheme])
        <div class="chip">{!! $icons->dot($procedureTheme->base(), 3) !!} {{ $procedure->getTranslation('name', $doc->locale) }}</div>
    @endforeach
    <div class="cover-details cover-sub">
        @if ($doc->firstName)<p>{{ $t('pdf.recipient', ['name' => $doc->firstName]) }}</p>@endif
        @if ($doc->date)<p>{{ $doc->formattedDate() }}</p>@endif
    </div>
    @if ($doc->hasDraft())<div class="notice">{{ $t('pdf.draft_notice') }}</div>@endif
@elseif ($kind === 'back')
    <div class="back-logo">
        @if (is_file(public_path($settings->get('brand.logo_path'))))
            <img src="{{ public_path($settings->get('brand.logo_path')) }}" width="32mm" alt="">
        @endif
    </div>
    <h2 style="color: {{ $primary->dark() }};">{{ $settings->localized('clinic.name', $doc->locale) }}</h2>
    <table width="100%" autosize="1" class="back-contacts">
        @foreach (['phone' => $settings->get('clinic.phone'), 'moon' => $t('pdf.back_after_hours', ['phone' => $settings->get('clinic.after_hours_phone')]), 'mail' => $settings->get('clinic.email'), 'pin' => $settings->localized('clinic.address', $doc->locale), 'clock' => $settings->localized('clinic.hours', $doc->locale)] as $icon => $text)
            <tr><td width="8mm">{!! $icons->image($icon, $primary->dark(), 5) !!}</td><td>{{ $text }}</td></tr>
        @endforeach
    </table>
    <table width="100%" autosize="1" class="qr"><tr>
        <td width="32mm"><img src="{{ $qr->dataUri($url) }}" width="32mm" height="32mm" alt=""></td>
        <td class="qr-caption">{{ $t('pdf.qr_caption') }}</td>
    </tr></table>
    <p class="url block"><a href="{{ $url }}">{!! $displayUrl !!}</a></p>
    <div class="back-emergency">{!! str_replace('112', '<b>112</b>', e($t('pdf.back_emergency'))) !!}</div>
    <div class="disclaimer">{{ $settings->localized('leaflet.disclaimer', $doc->locale) }} {{ $t('pdf.back_contact') }}</div>
    <p class="small">{{ $t('pdf.created', ['date' => \App\Support\DateFormat::date(now(), $doc->locale), 'version' => config('leaflet.content_version', 'v1')]) }}</p>
@elseif ($kind === 'redflags')
    <div class="redflag">
        <div class="danger"><table width="100%" autosize="1"><tr>
            <td width="10mm">{!! $icons->image('warning-triangle', '#FFFFFF', 7) !!}</td>
            <td><h2>{{ mb_strtoupper($t('pdf.red_flags'), 'UTF-8') }}</h2></td>
        </tr></table></div>
        <div class="redflag-body">
            @foreach ($groups as $items)
                @if (count($doc->sections) > 1 && $items->first()['heading'])
                    <p class="strong copy">{!! $icons->dot($items->first()['theme']->base(), 3) !!} {{ $items->first()['heading'] }}</p>
                @endif
                <table width="100%" autosize="1" class="item">
                    @foreach ($items as $block)
                        <tr><td class="icon">{!! $icons->dot('#D93B3B', 2.5) !!}</td><td>{{ $block['text'] }}</td></tr>
                    @endforeach
                </table>
            @endforeach
            <div class="contacts">
                <p class="strong">{{ $t('pdf.call_clinic') }}</p>
                <p class="phone">{{ $settings->get('clinic.phone') }}</p>
                <p>{{ $t('pdf.after_hours', ['phone' => $settings->get('clinic.after_hours_phone')]) }}</p>
            </div>
            <div class="emergency"><div>{{ $t('pdf.emergency') }}</div><div class="number">{{ $settings->get('clinic.emergency') }}</div></div>
        </div>
    </div>
@elseif ($kind === 'timeline')
    <h2 style="color: {{ $theme->dark() }};">{{ $t('pdf.recovery_timeline') }}</h2>
    @foreach ($groups as $items)
        @if (count($doc->sections) > 1)<p class="strong block" style="color: {{ $items->first()['theme']->dark() }};">{{ $items->first()['heading'] }}</p>@endif
        @foreach ($items as $block)
            <div class="timeline-step" style="border-color: {{ $block['theme']->base() }};">
                <table width="100%" autosize="1" class="heading"><tr>
                    <td width="9mm">{!! $icons->timelineCircle(min(4, $block['number']), $block['theme']) !!}</td>
                    <td style="background-color: {{ $block['theme']->tint() }}; color: {{ $block['theme']->dark() }}; padding: 2mm; font-weight: bold;">{{ $block['label'] }}</td>
                </tr></table>
                <p class="strong copy">{{ $block['title'] }}</p>
                <p>{{ $block['text'] }}</p>
            </div>
        @endforeach
    @endforeach
@elseif ($kind === 'cards' || $kind === 'topics')
    @foreach ($groups as $group => $items)
        @php
            $isCard = in_array($group, ['do', 'dont'], true);
            $colors = $isCard ? config('leaflet.colors.'.$group) : null;
            $icon = $isCard ? ($group === 'do' ? 'check' : 'cross') : $items->first()['icon'];
        @endphp
        <div class="{{ $isCard ? 'card' : 'topic' }}" @if ($colors)style="background-color: {{ $colors['tint'] }}; border-left: 1.5mm solid {{ $colors['base'] }};"@endif>
            <table width="100%" autosize="1" class="heading"><tr>
                <td width="9mm">{!! $isCard ? $icons->badge($icon, $colors['base']) : $icons->image($icon, $theme->dark(), 5) !!}</td>
                <td><h2 style="color: {{ $colors['dark'] ?? $theme->dark() }}; margin: 0;">{{ $items->first()['heading'] }}</h2></td>
            </tr></table>
            @if ($isCard)
                <table width="100%" autosize="1" class="item">
                    @foreach ($items as $block)
                        <tr><td class="icon">{!! $icons->image($icon, $colors['base'], 4) !!}</td><td>{{ $block['text'] }}</td></tr>
                    @endforeach
                </table>
            @else
                @foreach ($items as $block)<p>{{ $block['text'] }}</p>@endforeach
            @endif
        </div>
    @endforeach
    @if ($kind === 'topics' && $followUps !== [])
        @php
            $secondary = new \App\Services\Leaflet\ThemeColor($settings->get('brand.colors')['secondary']);
        @endphp
        <div class="followup" style="background-color: {{ $secondary->tint() }};">
            <table width="100%" autosize="1" class="heading"><tr>
                <td width="9mm">{!! $icons->image('calendar', $secondary->dark(), 5) !!}</td>
                <td><h2 style="color: {{ $secondary->dark() }}; margin: 0;">{{ $t('pdf.follow_up') }}</h2></td>
            </tr></table>
            @foreach ($followUps as ['procedure' => $procedure])
                <p>@if (count($doc->sections) > 1)<b>{{ $procedure->getTranslation('name', $doc->locale) }}</b><br>@endif{{ $procedure->getTranslation('follow_up', $doc->locale) }}</p>
            @endforeach
            @if ($doc->date)<p>{{ $t('pdf.procedure_date', ['date' => $doc->formattedDate()]) }}</p>@endif
        </div>
    @endif
@endif
@if ($truncated)
    <p class="full-guide">{{ $t('pdf.full_guide') }} <a href="{{ $url }}">{!! $displayUrl !!}</a></p>
@endif
