<?php

// DRAFT — must be reviewed by clinic medical staff before use.

$make = require __DIR__.'/_general.php';

return [
    $make([
        'slug' => 'botulinum-injection',
        'name' => ['Botuliinisüst', 'Botulinum injection'],
        'summary' => ['Süstiga mõjutatakse valitud lihaste tööd. Süstekohtades võib olla kerget punetust või väikesi sinikaid; toime kujuneb järk-järgult.', 'The injection affects selected muscles. Mild redness or small bruises may occur at injection sites; the effect develops gradually.'],
        'keywords' => ['botuliinisüst, botuliinisust, botuliintoksiin, botox, kortsusüst, kortsusust', 'botulinum injection, botulinum toxin, botox, wrinkle injection'],
        'follow_up' => ['Tulemuse hindamine toimub sageli umbes 2 nädala pärast, kui arst on selle määranud. Ära otsusta lisaravi vajadust enne kokkulepitud hindamist.', 'A review often takes place after about 2 weeks if your doctor has booked one. Wait for the agreed assessment before deciding about further treatment.'],
        'recovery' => ['Umbes 2 nädalat', 'Around 2 weeks'],
        'steps' => [
            [['Hoia süstekohti', 'Protect the injection sites'], ['Ära hõõru ega masseeri ravitud piirkonda. Järgi kliiniku antud juhiseid näohoolduse kohta.', 'Do not rub or massage the treated area. Follow your clinic’s instructions about facial care.']],
            [['Jälgi enesetunnet', 'Monitor how you feel'], ['Väikesed sinikad võivad püsida. Kui tekib uus silmalau vajumine või nägemishäire, võta kliinikuga ühendust.', 'Small bruises may remain. Contact the clinic if you develop a drooping eyelid or a change in vision.']],
            [['Lase toimel kujuneda', 'Allow the effect to develop'], ['Muutus võib ilmneda tasapisi. Ära masseeri piirkonda võimaliku ebaühtluse parandamiseks.', 'Changes may appear gradually. Do not massage the area to try to correct unevenness.']],
            [['Aruta tulemust kontrollis', 'Discuss the result at review'], ['Hinda tulemust koos ravimeeskonnaga kokkulepitud ajal. Toime tugevus ja kestus on individuaalsed.', 'Assess the result with your treatment team at the agreed time. The strength and duration of the effect vary between people.']],
        ],
        'advice' => [
            'do' => ['general.rest_first_day', ['Puuduta süstekohti ainult puhaste kätega.', 'Touch injection sites only with clean hands.'], ['Pane tähele muutusi nägemises ja neelamises.', 'Watch for changes in vision and swallowing.'], ['Järgi kliiniku antud piirkonnapõhiseid juhiseid.', 'Follow the clinic’s instructions for the area treated.']],
            'dont' => ['general.no_alcohol_24h', ['Ära masseeri süstitud piirkonda.', 'Do not massage the injected area.'], ['Väldi protseduuripäeval tugevat trenni.', 'Avoid strenuous exercise on the treatment day.'], ['Väldi protseduuripäeval sauna ja kuuma vanni.', 'Avoid saunas and hot baths on the treatment day.']],
            'hygiene' => [['Puhasta nahka õrnalt; meigi kasutamise kohta järgi kliiniku juhiseid.', 'Cleanse your skin gently; follow your clinic’s advice about make-up.']],
            'activity' => [['Naase tavategevuste juurde enesetunde järgi, vältides esialgu survet näole.', 'Return to usual activities as you feel able, avoiding pressure on your face initially.']],
            'sleep' => [['Väldi esimesel ööl otsest survet ravitud piirkonnale.', 'Avoid direct pressure on the treated area on the first night.']],
            'red_flag' => [['Kui sul tekib neelamisraskus või süvenev üldine lihasnõrkus, helista 112. Uue silmalau vajumise või topeltnägemise korral võta kohe ühendust kliinikuga.', 'Call 112 for difficulty swallowing or worsening general muscle weakness. Contact the clinic promptly for a new drooping eyelid or double vision.']],
        ],
    ]),
    $make([
        'slug' => 'dermal-filler',
        'name' => ['Täitesüst', 'Dermal filler'],
        'summary' => ['Täiteaine süstitakse naha alla või sügavamatesse kudedesse. Turse ja sinikad võivad esineda, kuid tugev valu või naha värvuse muutus vajab kiiret hindamist.', 'Filler is injected under the skin or into deeper tissues. Swelling and bruising may occur, but severe pain or a change in skin colour needs urgent assessment.'],
        'keywords' => ['täitesüst, taitesust, täiteaine, taiteaine, huulesüst, huulesust, huulte täitmine, huulte taitmine', 'dermal filler, lip filler, facial filler, filler injection'],
        'follow_up' => ['Kontroll toimub sageli 2–4 nädala jooksul, kui arst on selle määranud. Valu või naha värvuse muutuse korral ära oota kontrolliaega.', 'A review often takes place within 2–4 weeks if your doctor has booked one. Do not wait for the review if you have pain or a change in skin colour.'],
        'recovery' => ['2–4 nädalat', '2–4 weeks'],
        'steps' => [
            [['Jälgi naha värvust', 'Watch your skin colour'], ['Hoia piirkonda surve eest. Tugevnev valu, kahvatu või laiguline nahk vajab kohe kliiniku hinnangut.', 'Protect the area from pressure. Increasing pain, pale skin or mottling needs immediate assessment by the clinic.']],
            [['Lase tursel taanduda', 'Allow swelling to settle'], ['Turse võib alguses suureneda ja olla ebaühtlane. Ära püüa tulemust ise masseerides muuta.', 'Swelling may increase initially and be uneven. Do not try to change the result by massaging it yourself.']],
            [['Jälgi süstekohti', 'Watch the injection sites'], ['Sinikad võivad püsida. Kuumav punetus või süvenev hellus vajab kliinikuga arutamist.', 'Bruises may remain. Hot redness or increasing tenderness needs discussion with the clinic.']],
            [['Hinda tulemust koos arstiga', 'Review the result with your doctor'], ['Turse taandudes saab tulemust paremini hinnata. Püsiva tüki või ebamugavuse korral võta kliinikuga ühendust.', 'The result is easier to assess once swelling settles. Contact the clinic about a persistent lump or discomfort.']],
        ],
        'advice' => [
            'do' => ['general.rest_first_day', ['Jälgi naha värvust ja valu muutumist.', 'Monitor skin colour and changes in pain.'], ['Kasuta jahedat kompressi läbi puhta riide ilma surveta, kui kliinik seda soovitas.', 'Use a cool compress through a clean cloth without pressure if your clinic recommended it.'], ['Hoia kliiniku kontaktandmed käepärast.', 'Keep your clinic’s contact details to hand.']],
            'dont' => ['general.no_alcohol_24h', ['Ära masseeri ega pigista ravitud piirkonda ilma arsti juhiseta.', 'Do not massage or squeeze the treated area unless your doctor instructs you.'], ['Väldi tugevat trenni esimesel päeval.', 'Avoid strenuous exercise on the first day.'], ['Väldi sauna ja kuuma vanni, kuni turse on taandunud.', 'Avoid saunas and hot baths until swelling has settled.']],
            'eating' => [['Huulte ravi järel oota kuuma toidu ja joogiga, kuni tuimus on möödunud.', 'After lip treatment, wait until numbness wears off before having hot food or drinks.']],
            'hygiene' => [['Puhasta nahka õrnalt ja järgi kliiniku juhiseid meigi kasutamise kohta.', 'Cleanse your skin gently and follow your clinic’s advice about make-up.']],
            'activity' => [['Väldi tegevusi ja varustust, mis suruvad ravitud piirkonnale.', 'Avoid activities and equipment that press on the treated area.']],
            'sleep' => [['Maga esialgu nii, et ravitud piirkond ei oleks padja vastu surutud.', 'Initially sleep with the treated area free from pressure against the pillow.']],
            'red_flag' => [
                ['Tugeva või suureneva valu, kahvatu, sinaka või võrkja mustriga naha korral võta viivitamata ühendust kliinikuga; kui sa kliinikut kätte ei saa, mine erakorralise meditsiini osakonda.', 'Contact the clinic immediately for severe or increasing pain, pale or dusky skin, or a net-like skin pattern; if the clinic is unavailable, go to an emergency department.'],
                ['Äkilise nägemise halvenemise või nägemiskaotuse korral helista 112.', 'Call 112 for sudden deterioration or loss of vision.'],
            ],
        ],
    ]),
];
