<?php

return [
    'consent_version' => '2026-11-v1',

    'pdf' => [
        'format' => [148, 210],
        'margin_top' => 14,
        'margin_bottom' => 22,
        'margin_left' => 14,
        'margin_right' => 14,
        'margin_header' => 6,
        'margin_footer' => 7,
        'default_font' => 'sourcesans3',
        'default_font_size' => 10.5,
        'autoLangToFont' => false,
        'useSubstitutions' => false,
        'percentSubset' => 100,
        'tempDir' => storage_path('framework/cache/mpdf'),
    ],

    'font_directory' => resource_path('pdf/fonts'),
    'fonts' => [
        // mPDF has only R/B/I/BI slots: normal Nunito is 700; bold is 800.
        'nunito' => ['R' => 'Nunito-Bold.ttf', 'B' => 'Nunito-ExtraBold.ttf'],
        'sourcesans3' => [
            'R' => 'SourceSans3-Regular.ttf',
            'I' => 'SourceSans3-It.ttf',
            'B' => 'SourceSans3-Bold.ttf',
        ],
        // mPDF treats numeric 600 as bold. Use .strong (this family, normal
        // weight) for true 600; reserve sourcesans3 bold for actual 700.
        'sourcesans3sb' => ['R' => 'SourceSans3-Semibold.ttf'],
    ],

    'colors' => [
        'do' => ['base' => '#2F9E5B', 'dark' => '#1E7A43', 'tint' => '#EAF6EF'],
        'dont' => ['base' => '#D93B3B', 'dark' => '#B42323', 'tint' => '#FDECEC'],
        'warn' => ['base' => '#F5A524', 'dark' => '#8A5A00', 'tint' => '#FFF6E5'],
        'muted' => '#6B7280',
        'hairline' => '#DCE3EE',
    ],

    'print' => [
        'sheet' => [168, 230],
        'bleed' => 3,
        'margin_inner' => 16,
        'margin_outer' => 12,
    ],
    'draft_watermark' => true,
];
