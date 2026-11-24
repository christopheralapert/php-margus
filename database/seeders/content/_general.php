<?php

// DRAFT — must be reviewed by clinic medical staff before use.

$shared = [
    'general.no_smoking_48h' => ['dont', 'Ära suitseta ega kasuta veipi vähemalt 48 tunni jooksul pärast protseduuri.', 'Do not smoke or vape for at least 48 hours after the procedure.'],
    'general.no_alcohol_24h' => ['dont', 'Väldi alkoholi vähemalt 24 tunni jooksul pärast protseduuri.', 'Avoid alcohol for at least 24 hours after the procedure.'],
    'general.no_sauna_week1' => ['dont', 'Väldi esimesel nädalal sauna ja kuuma vanni.', 'Avoid saunas and hot baths during the first week.'],
    'general.keep_wound_clean' => ['do', 'Hoia haav puhas ja järgi kliinikust saadud haavahoolduse juhiseid.', 'Keep the wound clean and follow the wound care instructions from your clinic.'],
    'general.rest_first_day' => ['do', 'Võta esimesel päeval rahulikult ja puhka vastavalt enesetundele.', 'Take things gently on the first day and rest as you need.'],
    'general.hydrate' => ['eating', 'Joo regulaarselt vett, kui arst ei ole soovitanud vedelikku piirata.', 'Drink water regularly unless your doctor has advised you to limit fluids.'],
    'general.pain_as_prescribed' => ['pain', 'Kasuta valuvaigistit nii, nagu arst on määranud. Kui sa pole kindel, küsi arstilt või apteekrilt, milline valuvaigisti sulle sobib.', 'Use pain relief as prescribed by your doctor. If you are unsure, ask your doctor or pharmacist which pain relief suits you.'],
    'general.red_flag_fever' => ['red_flag', 'Kui palavik tõuseb üle 38 °C, võta samal päeval ühendust kliinikuga.', 'Contact the clinic the same day if your temperature rises above 38 °C.'],
    'general.red_flag_112' => ['red_flag', 'Kui sul tekib hingamisraskus, rindkerevalu, tugev verejooks või minestus, helista 112.', 'Call 112 if you develop difficulty breathing, chest pain, heavy bleeding or fainting.'],
];

// Category files use [Estonian, English] pairs; expand them to model attributes here.
return static function (array $definition) use ($shared): array {
    $translate = static fn (array $pair): array => ['et' => $pair[0], 'en' => $pair[1]];
    $markers = [
        ['0–24 h', 'First 24 h'],
        ['Päevad 1–3', 'Days 1–3'],
        ['1. nädal', 'Week 1'],
        $definition['recovery'] ?? ['Kuni paranemiseni', 'Until healed'],
    ];
    $phases = ['first_24h', 'days_1_3', 'week_1', 'full_recovery'];
    $steps = [];

    foreach ($definition['steps'] as $sort => [$title, $body]) {
        $steps[] = ['phase' => $phases[$sort], 'marker' => $translate($markers[$sort]), 'title' => $translate($title), 'body' => $translate($body), 'sort' => $sort];
    }

    // These four shared items are appropriate for every example procedure.
    $advice = [];
    foreach (['general.pain_as_prescribed', 'general.hydrate', 'general.red_flag_fever', 'general.red_flag_112'] as $key) {
        [$section, $et, $en] = $shared[$key];
        $advice[] = ['section' => $section, 'text' => $translate([$et, $en]), 'merge_key' => $key, 'sort' => count($advice)];
    }

    foreach ($definition['advice'] as $section => $items) {
        foreach ($items as $item) {
            $key = is_string($item) ? $item : null;
            if ($key !== null) {
                [$sharedSection, $et, $en] = $shared[$key];
                if ($sharedSection !== $section) {
                    throw new LogicException('Shared advice section mismatch: '.$key);
                }
                $item = [$et, $en];
            }
            $advice[] = ['section' => $section, 'text' => $translate($item), 'merge_key' => $key, 'sort' => count($advice)];
        }
    }

    return [
        'slug' => $definition['slug'],
        'name' => $translate($definition['name']),
        'summary' => $translate($definition['summary']),
        'search_keywords' => $translate($definition['keywords']),
        'follow_up' => $translate($definition['follow_up']),
        'timeline_steps' => $steps,
        'advice_items' => $advice,
    ];
};
