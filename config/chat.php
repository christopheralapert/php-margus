<?php

return [
    'enabled' => env('CHAT_ENABLED', false),
    'provider' => env('LLM_PROVIDER', 'anthropic'),
    'api_key' => env('LLM_API_KEY', ''),
    'model' => env('LLM_MODEL', 'claude-opus-5-5'),
    'base_url' => env('LLM_BASE_URL'),
    'effort' => env('LLM_EFFORT', 'low'),
    'timeout' => (int) env('LLM_TIMEOUT', 20),
    'daily_cap' => (int) env('CHAT_DAILY_CAP', 500),
    'max_chars' => (int) env('CHAT_MAX_CHARS', 500),

    // Both languages apply regardless of the requested reply language.
    'red_flags' => [
        'ei saa hingata', 'hingamisraskus', 'rindkerevalu', 'valu rinnus',
        'minestus', 'teadvuse kaotus', 'tugev verejooks', 'veri ei peatu',
        'kõrge palavik', 'palavik üle 39', 'krambid', 'näo turse',
        'keele turse', 'allergiline reaktsioon',
        "can't breathe", 'cannot breathe', 'chest pain', 'fainted', 'passed out',
        'heavy bleeding', "bleeding won't stop", 'high fever', 'seizure',
        'face swelling', 'tongue swelling', 'allergic reaction', 'anaphylaxis',
    ],
    // Stems also recognise inflected symptom words in procedure advice.
    'symptom_stems' => [
        'hinga', 'rindkere', 'valu', 'minest', 'teadvus', 'verejooks', 'veri',
        'palavik', 'krambi', 'turse', 'allerg', 'breathe', 'pain', 'faint',
        'bleed', 'fever', 'seizure', 'swelling', 'anaphyl',
    ],
];
