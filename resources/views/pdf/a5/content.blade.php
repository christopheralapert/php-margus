<div class="section-band mb-s6" style="background-color: {{ $theme->base() }}; color: {{ $theme->on() }};">
    <h1>{{ $procedure->getTranslation('name', $doc->locale) }}</h1>
</div>
<p class="mb-s6">{{ $procedure->getTranslation('summary', $doc->locale) }}</p>
@foreach ([\App\Enums\AdviceSection::Do, \App\Enums\AdviceSection::Dont] as $adviceSection)
    @php($items = $procedure->adviceItems->where('section', $adviceSection))
    @if ($items->isNotEmpty())
        <div class="keep mb-s6">
            <h2 style="color: {{ $theme->dark() }}">{{ __('enums.advice_section.'.$adviceSection->value, [], $doc->locale) }}</h2>
            <ul>
                @foreach ($items as $item)
                    <li>{{ $item->getTranslation('text', $doc->locale) }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endforeach
