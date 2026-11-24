<?php

return [
    'clinic' => [
        'name' => ['et' => 'Lumi Kliinik', 'en' => 'Lumi Clinic'],
        'phone' => '+372 600 0000',
        'after_hours_phone' => '+372 600 0001',
        'emergency' => '112',
        'email' => 'info@lumikliinik.example',
        'address' => ['et' => 'Näidise tn 1, 10111 Tallinn', 'en' => 'Näidise tn 1, 10111 Tallinn, Estonia'],
        'hours' => ['et' => 'E–R 8.00–18.00', 'en' => 'Mon–Fri 8:00–18:00'],
        'website' => null,
    ],
    'brand' => [
        'logo_path' => 'brand/logo.svg',
        'colors' => [
            'primary' => '#2E6FD8',
            'secondary' => '#2BB3A3',
            'accent' => '#F5A524',
            'danger' => '#D93B3B',
            'ink' => '#1F2937',
            'paper' => '#FFFFFF',
            'soft' => '#F3F7FC',
        ],
    ],
    'leaflet' => [
        'disclaimer' => [
            'et' => 'See juhend ei asenda arsti isiklikku nõuannet.',
            'en' => "This leaflet does not replace your doctor's personal advice.",
        ],
    ],
    'retention' => [
        'saved_leaflet_months' => 12,
        'unverified_account_days' => 30,
    ],
];
