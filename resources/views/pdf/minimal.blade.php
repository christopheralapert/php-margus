@inject('settings', 'App\Support\Settings')
<!DOCTYPE html>
<html lang="{{ $doc->locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ $doc->title() }}</title>
    <style>body { font-size: 11pt; line-height: 1.4; } h1 { font-size: 20pt; } h2 { font-size: 16pt; } h3 { font-size: 12pt; } .red-flags { border: 1px solid #B42323; padding: 4mm; }</style>
</head>
<body>
    <h1>{{ $doc->title() }}</h1>
    @include('wizard.personal-details')
    @if ($doc->showDraftMarks())<p>{{ __('wizard.draft', [], $doc->locale) }}</p>@endif
    @foreach ($doc->sections as ['procedure' => $procedure])
        <h2>{{ $procedure->getTranslation('name', $doc->locale) }}</h2>
        @foreach ([\App\Enums\AdviceSection::Do, \App\Enums\AdviceSection::Dont, \App\Enums\AdviceSection::RedFlag] as $section)
            <div class="{{ $section === \App\Enums\AdviceSection::RedFlag ? 'red-flags' : '' }}">
                <h3>{{ __('enums.advice_section.'.$section->value, [], $doc->locale) }}</h3>
                <ul>
                    @foreach ($procedure->adviceItems->where('section', $section) as $item)
                        <li>{{ $item->getTranslation('text', $doc->locale) }}</li>
                    @endforeach
                </ul>
                @if ($section === \App\Enums\AdviceSection::RedFlag) @include('wizard.contacts') @endif
            </div>
        @endforeach
    @endforeach
</body>
</html>
