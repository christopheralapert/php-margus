<?php

// DRAFT — must be reviewed by clinic medical staff before use.

$make = require __DIR__.'/_general.php';

return [
    $make([
        'slug' => 'mole-removal',
        'name' => ['Sünnimärgi eemaldamine', 'Mole removal'],
        'summary' => ['Sünnimärgi eemaldamise järel vajab nahahaav kaitset ja hoolt. Sideme ja õmbluste hooldus sõltub eemaldamise viisist ning haava asukohast.', 'After mole removal, the skin wound needs protection and care. Dressing and suture care depend on the removal method and the wound’s location.'],
        'keywords' => ['sünnimärgi eemaldamine, sunnimargi eemaldamine, neevus, nahamoodustis, sünnimärk, sunnimark', 'mole removal, naevus, skin lesion, skin excision'],
        'follow_up' => ['Tule haavakontrolli või õmbluste eemaldamisele tavaliselt 1–2 nädala jooksul, kui arst on selle määranud. Uuringuvastuse saamise viis lepi kliinikuga kokku.', 'Attend a wound check or suture removal, usually within 1–2 weeks, if your doctor has booked one. Agree with the clinic how you will receive any laboratory result.'],
        'recovery' => ['2–4 nädalat', '2–4 weeks'],
        'steps' => [
            [['Kaitse sidet', 'Protect the dressing'], ['Jäta esmane side paigale kliiniku juhiste järgi. Väike vereplekk võib tekkida, kuid jälgi selle suurenemist.', 'Leave the initial dressing in place as instructed by your clinic. A small blood stain may appear; watch for it spreading.']],
            [['Hoolda haava', 'Care for the wound'], ['Vaheta märg või määrdunud side kliiniku õpetatud viisil. Pärast lubatud pesemist kuivata haava ümbrus õrnalt tupsutades.', 'Change a wet or dirty dressing as shown by your clinic. After washing is allowed, gently pat the surrounding skin dry.']],
            [['Väldi haava venitamist', 'Avoid stretching the wound'], ['Hoia õmblusi hõõrdumise ja venituse eest. Tule kontrolli kokkulepitud ajal.', 'Protect sutures from friction and stretching. Attend your check at the agreed time.']],
            [['Kaitse paranenud nahka', 'Protect the healed skin'], ['Kui haav on sulgunud, kaitse armi päikese eest riiete ja sobiva päikesekaitsega. Armi välimus võib muutuda veel mitu kuud.', 'Once the wound has closed, protect the scar from sunlight with clothing and suitable sun protection. Its appearance may continue to change for months.']],
        ],
        'advice' => [
            'do' => ['general.keep_wound_clean', 'general.rest_first_day', ['Pese käsi enne sideme vahetamist.', 'Wash your hands before changing the dressing.'], ['Küsi kliinikust, kuidas saad koeproovi vastuse.', 'Ask the clinic how you will receive the tissue sample result.']],
            'dont' => ['general.no_smoking_48h', 'general.no_alcohol_24h', 'general.no_sauna_week1', ['Ära noki koorikut ega eemalda ise õmblusi.', 'Do not pick the scab or remove sutures yourself.']],
            'hygiene' => [['Ära leota haava vannis ega basseinis enne selle sulgumist ja kliiniku luba.', 'Do not soak the wound in a bath or pool until it has closed and your clinic allows it.']],
            'activity' => [['Väldi liigutusi ja trenni, mis tõmbavad haavaservi laiali.', 'Avoid movements and exercise that pull the wound edges apart.']],
            'sleep' => [['Vali magamisasend, milles haavale ei lange survet.', 'Choose a sleeping position that avoids pressure on the wound.']],
            'red_flag' => [['Kui punetus levib, haavast tuleb mäda või haavaservad avanevad, võta samal päeval ühendust kliinikuga.', 'Contact the clinic the same day if redness spreads, pus appears or the wound edges open.']],
        ],
    ]),
    $make([
        'slug' => 'cryotherapy',
        'name' => ['Krüoteraapia', 'Cryotherapy'],
        'summary' => ['Nahamoodustist töödeldakse külmaga. Ravitud kohale võib tekkida vill või koorik, mida tuleb kaitsta hõõrdumise eest.', 'A skin lesion is treated by freezing. A blister or scab may form and needs protection from friction.'],
        'keywords' => ['krüoteraapia, kruoteraapia, külmaravi, kulmaravi, soolatüügas, soolatuugas, vedel lämmastik, vedel lammastik', 'cryotherapy, freezing, liquid nitrogen, wart treatment'],
        'follow_up' => ['Kontroll võib toimuda mõne nädala pärast, kui arst on selle määranud. Kui koht ei parane või moodustis jääb alles, küsi kliinikust edasist nõu.', 'A review may take place after a few weeks if your doctor has booked one. Ask the clinic for advice if the area does not heal or the lesion remains.'],
        'recovery' => ['2–4 nädalat', '2–4 weeks'],
        'steps' => [
            [['Lase nahal rahuneda', 'Let the skin settle'], ['Kipitust ja punetust võib esineda pärast külmutamist. Kaitse kohta hõõrdumise eest.', 'Stinging and redness may occur after freezing. Protect the area from rubbing.']],
            [['Hoia villi tervena', 'Keep the blister intact'], ['Võib tekkida läbipaistev või verine vill. Ära torka seda ise katki; suure või valuliku villi korral küsi kliinikust nõu.', 'A clear or blood-filled blister may form. Do not pierce it yourself; ask the clinic about a large or painful blister.']],
            [['Lase koorikul eralduda', 'Let the scab separate'], ['Koorik võib tekkida ja hiljem ise ära tulla. Kui koht hõõrdub vastu riideid, kata see puhta mittekleepuva sidemega.', 'A scab may form and later come away by itself. Use a clean non-stick dressing if clothing rubs the area.']],
            [['Jälgi uut nahka', 'Watch the new skin'], ['Paranemise aeg sõltub ravitud koha suurusest ja asukohast. Kaitse paranenud nahka päikese eest.', 'Healing time depends on the size and location of the treated area. Protect healed skin from sunlight.']],
        ],
        'advice' => [
            'do' => ['general.rest_first_day', ['Pese ravitud kohta õrnalt ja kuivata tupsutades.', 'Wash the treated area gently and pat it dry.'], ['Kata hõõrduv koht puhta mittekleepuva sidemega.', 'Cover an area exposed to rubbing with a clean non-stick dressing.'], ['Kui vill avaneb, hoia see puhas ja küsi vajaduse korral sidumisnõu.', 'If the blister opens, keep it clean and ask for dressing advice if needed.']],
            'dont' => [['Ära torka villi katki.', 'Do not pierce the blister.'], ['Ära tõmba koorikut ära.', 'Do not pull off the scab.'], ['Ära kasuta ravitud kohal söövitavaid käsimüügivahendeid ilma kliinikuga nõu pidamata.', 'Do not apply caustic over-the-counter treatments to the area without checking with your clinic.'], ['Ära leota lahtist villi basseinis ega vannis.', 'Do not soak an open blister in a pool or bath.']],
            'hygiene' => [['Pese käsi enne ravitud naha puudutamist ja sideme vahetamist.', 'Wash your hands before touching treated skin or changing its dressing.']],
            'activity' => [['Vähenda tegevusi, mis hõõruvad ravitud kohta või teevad haiget.', 'Reduce activities that rub the treated area or cause pain.']],
            'sleep' => [['Hoia ravitud kohta öösel hõõrdumise eest; vajaduse korral kasuta puhast sidet.', 'Protect the treated area from rubbing overnight; use a clean dressing if needed.']],
            'red_flag' => [['Kui punetus levib, tekib mäda või valu järjest tugevneb, võta samal päeval ühendust kliinikuga.', 'Contact the clinic the same day if redness spreads, pus develops or pain keeps increasing.']],
        ],
    ]),
];
