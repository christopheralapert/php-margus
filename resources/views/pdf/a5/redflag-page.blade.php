<pagebreak page-selector="redflags" />
@include('pdf.a5.redflag')
@include('pdf.a5.followup', ['procedures' => array_column($doc->sections, 'procedure')])
