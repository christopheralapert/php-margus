<?php

// DRAFT — must be reviewed by clinic medical staff before use.

$make = require __DIR__.'/_general.php';

return [
    $make([
        'slug' => 'tonsillectomy',
        'name' => ['Kurgumandlite eemaldamine', 'Tonsillectomy'],
        'summary' => ['Kurgumandlite eemaldamise järel vajab neel paranemiseks aega. Kurguvalu võib kiirguda kõrvadesse; värske verejooks vajab kiiret abi.', 'Your throat needs time to heal after the tonsils are removed. Throat pain may spread to the ears; fresh bleeding needs urgent help.'],
        'keywords' => ['kurgumandlite eemaldamine, mandlioperatsioon, tonsillektoomia, mandlid, kurgumandlid', 'tonsillectomy, tonsil removal, tonsil operation, throat surgery'],
        'follow_up' => ['Kontroll toimub sageli mõne nädala jooksul, kui arst on selle määranud. Värske verejooksu korral ära oota kontrolliaega.', 'A review often takes place within a few weeks if your doctor has booked one. Do not wait for the review if fresh bleeding occurs.'],
        'recovery' => ['2–3 nädalat', '2–3 weeks'],
        'steps' => [
            [['Joo ja puhka', 'Drink and rest'], ['Joo väikeste lonksudena ja söö kliinikust saadud juhiste järgi. Pärast narkoosi olgu sinuga vastutav täiskasvanu vastavalt haigla juhistele.', 'Take small sips and eat as advised by your clinic. After general anaesthesia, have a responsible adult with you as instructed by the hospital.']],
            [['Hoia vedelikutarbimist', 'Keep up your fluids'], ['Neelamine võib olla valus. Kasuta valuvaigistit nii, nagu arst on määranud, et saaksid juua ja süüa.', 'Swallowing may hurt. Use pain relief as prescribed by your doctor so that you can drink and eat.']],
            [['Ole verejooksu suhtes tähelepanelik', 'Stay alert for bleeding'], ['Valu võib esimesel nädalal tugevneda ja neelus võib olla hele katt. Ära eemalda seda; värske veri ei kuulu tavapärase paranemise juurde.', 'Pain may increase during the first week and a pale coating may appear in the throat. Do not remove it; fresh blood is not part of routine healing.']],
            [['Taasta koormus tasapisi', 'Build up activity gradually'], ['Verejooksu oht võib püsida ka teisel nädalal. Tööle, kooli ja trenni naasmise aeg lepi kokku ravimeeskonnaga.', 'Bleeding can still occur during the second week. Agree with your treatment team when to return to work, school and exercise.']],
        ],
        'advice' => [
            'do' => ['general.rest_first_day', ['Joo sageli väikeste lonksudena, kui neelamine on valus.', 'Take frequent small sips if swallowing hurts.'], ['Söö regulaarselt kliiniku juhiste järgi.', 'Eat regularly according to your clinic’s advice.'], ['Hoia abi kutsumiseks telefon ja kontaktandmed lähedal.', 'Keep a phone and contact details nearby to call for help.']],
            'dont' => ['general.no_smoking_48h', 'general.no_alcohol_24h', 'general.no_sauna_week1', ['Ära kraabi kurku ega püüa heledat kattu eemaldada.', 'Do not scrape your throat or try to remove the pale coating.']],
            'eating' => [['Vali toit, mida saad neelata, ja väldi väga kuuma sööki ning jooki. Järgi haigla toitumisjuhiseid.', 'Choose food you can swallow and avoid very hot food and drinks. Follow the hospital’s dietary advice.']],
            'hygiene' => [['Pese hambaid õrnalt; väldi jõulist kuristamist.', 'Brush your teeth gently; avoid forceful gargling.']],
            'activity' => [['Väldi tugevat pingutust paranemise ajal ja püsi kohas, kust saad kiiresti arstiabi.', 'Avoid strenuous exertion while healing and stay where medical help is readily accessible.']],
            'sleep' => [['Puhka mugavas asendis ja hoia joogivesi käepärast.', 'Rest in a comfortable position and keep drinking water within reach.']],
            'red_flag' => [
                ['Kui suust tuleb värsket verd, sülitad verehüübeid või oksendad verd, pöördu kohe erakorralise meditsiini osakonda. Aktiivse verejooksu või nõrkuse korral helista 112; ära juhi ise autot.', 'Go to an emergency department immediately for fresh blood from your mouth, blood clots or vomiting blood. Call 112 for active bleeding or weakness; do not drive yourself.'],
                ['Kui sa ei saa vedelikku neelata või urineerid märgatavalt vähem, võta kohe ühendust kliinikuga.', 'Contact the clinic promptly if you cannot swallow fluids or are passing much less urine.'],
            ],
        ],
    ]),
    $make([
        'slug' => 'nosebleed-cauterisation',
        'name' => ['Ninaverejooksu kõrvetusravi', 'Nosebleed cauterisation'],
        'summary' => ['Veritsevat kohta ninas töödeldakse verejooksu peatamiseks. Nina limaskest vajab paranemiseks rahu ja õrna hooldust.', 'A bleeding point inside the nose is treated to help stop bleeding. The nasal lining needs gentle care while it heals.'],
        'keywords' => ['ninaverejooks, nina kõrvetamine, nina korvetamine, kauterisatsioon, veritsev nina', 'nosebleed, cautery, nose cauterisation, bleeding nose'],
        'follow_up' => ['Kontroll toimub vajaduse korral paari nädala jooksul, kui arst on selle määranud. Korduvate verejooksude puhul võta kliinikuga varem ühendust.', 'A review may take place within a couple of weeks if your doctor has booked one. Contact the clinic sooner for repeated bleeding.'],
        'recovery' => ['1–2 nädalat', '1–2 weeks'],
        'steps' => [
            [['Hoia nina rahus', 'Let your nose rest'], ['Ära nuuska ega noki nina. Aevasta avatud suuga, et vähendada survet ninas.', 'Do not blow or pick your nose. Sneeze with your mouth open to reduce pressure in the nose.']],
            [['Kaitse koorikut', 'Protect the scab'], ['Ravitud kohale võib tekkida koorik. Kasuta määratud ninahooldusvahendit ainult nii, nagu arst on määranud.', 'A scab may form at the treated spot. Use prescribed nasal treatment only as directed by your doctor.']],
            [['Väldi ärritust', 'Avoid irritation'], ['Ära eemalda koorikut ka siis, kui see häirib. Kui verejooks kordub, järgi allpool toodud survevõtet.', 'Do not remove the scab even if it bothers you. If bleeding recurs, follow the pressure technique below.']],
            [['Naase tavarütmi', 'Return to your usual routine'], ['Suurenda koormust tasapisi, kui verejooks ei kordu. Püsiva ninakinnisuse või korduva veritsuse korral küsi kliinikust nõu.', 'Increase activity gradually if bleeding does not recur. Ask the clinic about persistent blockage or repeated bleeding.']],
        ],
        'advice' => [
            'do' => ['general.rest_first_day', ['Aevasta avatud suuga.', 'Sneeze with your mouth open.'], ['Verejooksu korral istu ettepoole kallutatult ja pigista nina pehmet osa järjest 15 minutit.', 'For bleeding, sit leaning forwards and pinch the soft part of your nose continuously for 15 minutes.'], ['Sülita suhu valguv veri välja.', 'Spit out blood that runs into your mouth.']],
            'dont' => ['general.no_alcohol_24h', ['Ära nuuska tugevalt ega noki nina paranemise ajal.', 'Do not blow your nose forcefully or pick it while it heals.'], ['Väldi esimestel päevadel kuumi jooke ja kuuma sauna.', 'Avoid hot drinks and hot saunas during the first few days.'], ['Ära kalluta verejooksu ajal pead taha.', 'Do not tilt your head back during a nosebleed.']],
            'hygiene' => [['Puhasta nina ümbrust õrnalt; ära lükka vatitikku ega salvrätti sügavale ninna.', 'Clean around your nose gently; do not push cotton buds or tissues deep inside it.']],
            'activity' => [['Väldi esimestel päevadel tugevat pingutust ja rasket tõstmist.', 'Avoid strenuous exertion and heavy lifting during the first few days.']],
            'sleep' => [['Puhka esimesel ööl pea veidi kõrgemal.', 'Rest with your head slightly raised on the first night.']],
            'red_flag' => [['Kui verejooks ei peatu pärast 15–20 minutit katkematut survet, otsi kohe erakorralist abi ja teavita kliinikut. Tugeva verejooksu või nõrkuse korral helista 112.', 'Seek urgent medical help and inform the clinic if bleeding does not stop after 15–20 minutes of continuous pressure. Call 112 for heavy bleeding or weakness.']],
        ],
    ]),
];
