<!DOCTYPE html>
<html lang="{{ $doc->locale }}">
<head>
    <meta charset="utf-8">
</head>
<body>
    <htmlpageheader name="primary">
        @include('pdf.a5.strip', ['color' => $settings->get('brand.colors')['primary']])
    </htmlpageheader>
    @foreach ($doc->sections as $index => $section)
        <htmlpageheader name="section-{{ $index }}">
            @include('pdf.a5.strip', ['color' => $section['theme']->base()])
        </htmlpageheader>
    @endforeach
    <htmlpagefooter name="running">@include('pdf.a5.footer')</htmlpagefooter>
    <htmlpageheader name="redflags">
        @include('pdf.a5.strip', ['color' => '#D93B3B'])
    </htmlpageheader>

    @unless ($categoryProof ?? false)
        @include('pdf.a5.cover')
        @if ($doc->general() !== [])
            <pagebreak page-selector="general" />
            @include('pdf.a5.general', ['isGeneral' => true])
        @endif
    @endunless

    @foreach ($doc->sections as $index => $section)
        <pagebreak page-selector="section-{{ $index }}" />
        @include('pdf.a5.procedure', $section)
    @endforeach
    @unless ($categoryProof ?? false)
        @include('pdf.a5.redflag-page')
        @for ($note = 0; $note < ($notesPages ?? 0); $note++)
            <pagebreak page-selector="notes" />
            @include('pdf.a5.notes')
        @endfor
        <pagebreak page-selector="back" />
        @include('pdf.a5.back')
    @endunless
</body>
</html>
