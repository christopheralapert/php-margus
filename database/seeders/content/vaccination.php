<?php

// DRAFT — must be reviewed by clinic medical staff before use.

$make = require __DIR__.'/_general.php';

return [
    $make([
        'slug' => 'vaccination',
        'name' => ['Vaktsineerimine', 'Vaccination'],
        'summary' => ['Vaktsiin aitab immuunsüsteemil nakkuse vastu kaitset kujundada. Süstekoha hellus ja kerge halb enesetunne võivad esineda; täpne järelhooldus sõltub vaktsiinist.', 'A vaccine helps your immune system develop protection against infection. A sore injection site and feeling mildly unwell may occur; specific aftercare depends on the vaccine.'],
        'keywords' => ['vaktsineerimine, vaktsiin, kaitsesüst, kaitsesust, süst, sust, immuniseerimine', 'vaccination, vaccine, jab, immunisation, injection'],
        'follow_up' => ['Tule järgmisele vaktsineerimisele või kontrolli kokkulepitud ajal, kui arst on selle määranud. Vaktsineerimiskava sõltub vaktsiinist ja sinu varasemast kaitsest.', 'Attend the next vaccination or review at the agreed time if your doctor has booked one. The schedule depends on the vaccine and your previous protection.'],
        'recovery' => ['Järgmised nädalad', 'Following weeks'],
        'steps' => [
            [['Oota jälgimisaja lõpuni', 'Complete the observation period'], ['Püsi pärast süsti kliinikus nii kaua, kui ravimeeskond palub. Teata kohe sügelusest, hingamisraskusest või pearinglusest.', 'Stay at the clinic after the injection for as long as your treatment team asks. Report itching, breathing difficulty or dizziness immediately.']],
            [['Jälgi süstekohta', 'Watch the injection site'], ['Süstekoht võib olla hell ja punetada. Hoia jahedat puhast kompressi õrnalt nahal, kui see leevendab vaevusi.', 'The injection site may be sore and red. Gently hold a cool clean compress against it if this helps.']],
            [['Naase tavarütmi', 'Return to your usual routine'], ['Enesetunde paranedes jätka tavategevusi. Mõne vaktsiini reaktsioon võib tekkida hiljem; järgi konkreetse vaktsiini infolehte.', 'Resume usual activities as you feel better. Reactions to some vaccines can occur later; follow the information for your specific vaccine.']],
            [['Hoia vaktsineerimisinfo alles', 'Keep your vaccination record'], ['Kaitse kujunemise aeg erineb vaktsiinide vahel. Kontrolli ravimeeskonnaga, kas vajad järgmisi vaktsineerimisi.', 'The time needed for protection to develop varies between vaccines. Check with your treatment team whether further vaccinations are needed.']],
        ],
        'advice' => [
            'do' => ['general.rest_first_day', ['Täida kliinikus soovitatud jälgimisaeg.', 'Complete the observation period advised by the clinic.'], ['Liiguta süstitud kätt õrnalt enesetunde järgi.', 'Move the injected arm gently as comfort allows.'], ['Hoia vaktsiini nimetus ja manustamise kuupäev alles.', 'Keep a record of the vaccine name and date.']],
            'dont' => [['Ära hõõru ega masseeri süstekohta.', 'Do not rub or massage the injection site.'], ['Ära kata süstekohta määrdunud või liiga tiheda sidemega.', 'Do not cover the injection site with a dirty or overly tight dressing.'], ['Ära sunni end tugevalt treenima, kui tunned end halvasti.', 'Do not push yourself to exercise strenuously if you feel unwell.'], ['Ära lahku enne soovitatud jälgimisaja lõppu.', 'Do not leave before the advised observation period ends.']],
            'hygiene' => [['Süstekohta võib õrnalt pesta; kuivata nahk tupsutades ja väldi hõõrumist.', 'You can wash the injection site gently; pat the skin dry and avoid rubbing.']],
            'activity' => [['Tavapärane kerge liikumine sobib enesetunde järgi.', 'Usual gentle activity is suitable as you feel able.']],
            'sleep' => [['Puhka vajaduse järgi ja vali asend, mis ei suru valulikule süstekohale.', 'Rest as needed and choose a position that avoids pressure on a sore injection site.']],
            'red_flag' => [['Kui süstekoha punetus levib kiiresti, tekib mäda või enesetunne halveneb märgatavalt, võta samal päeval ühendust kliinikuga.', 'Contact the clinic the same day if redness spreads rapidly, pus appears or you feel significantly worse.']],
        ],
    ]),
    $make([
        'slug' => 'allergy-injection',
        'name' => ['Allergeeni immuunravi süst', 'Allergen immunotherapy injection'],
        'summary' => ['Allergeeni immuunravi süst on osa arsti juhitud allergiaravist. Reaktsioon võib tekkida ka pärast kliinikust lahkumist, seetõttu järgi jälgimis- ja hädaabijuhiseid.', 'An allergen immunotherapy injection is part of specialist-led allergy treatment. A reaction can occur after you leave the clinic, so follow your observation and emergency instructions.'],
        'keywords' => ['allergiasüst, allergiasust, allergeeni immuunravi, immuunravi, hüposensibiliseerimine, huposensibiliseerimine, allergiavaktsiin', 'allergy injection, allergen immunotherapy, desensitisation, allergy shot'],
        'follow_up' => ['Järgmine visiit toimub individuaalse raviplaani järgi, kui arst on selle määranud. Teata enne järgmist süsti kõigist eelnenud reaktsioonidest ja tervisemuutustest.', 'The next visit follows your individual treatment plan if your doctor has booked one. Report all previous reactions and changes in health before the next injection.'],
        'recovery' => ['Enne järgmist visiiti', 'Before the next visit'],
        'steps' => [
            [['Jää jälgimisele', 'Stay for observation'], ['Püsi kliinikus vähemalt 30 minutit ja kauem, kui ravimeeskond seda palub. Teata kohe sügelusest, köhast, hingamisraskusest või pearinglusest.', 'Stay at the clinic for at least 30 minutes and longer if your treatment team asks. Report itching, coughing, breathing difficulty or dizziness immediately.']],
            [['Pane reaktsioon tähele', 'Watch for a reaction'], ['Süstekoha turse võib tekkida hiljem. Märgi selle ulatus ja kestus üles ning teavita kliinikut suurest või süvenevast reaktsioonist.', 'Injection-site swelling may appear later. Note its extent and duration and tell the clinic about a large or worsening reaction.']],
            [['Jätka kokkulepitud plaani', 'Continue your agreed plan'], ['Kui vaevused püsivad või tekib uus reaktsioon, küsi kliinikust nõu. Ära muuda ise raviplaani.', 'Ask the clinic for advice if symptoms persist or a new reaction occurs. Do not change your treatment plan yourself.']],
            [['Valmistu järgmiseks visiidiks', 'Prepare for your next visit'], ['Räägi enne järgmist süsti reaktsioonidest, haigestumisest ja hingamiskaebustest. Järgmise ravi sobivuse otsustab ravimeeskond.', 'Before the next injection, report reactions, illness and breathing symptoms. Your treatment team will decide whether the next treatment is appropriate.']],
        ],
        'advice' => [
            'do' => ['general.rest_first_day', ['Jää pärast süsti vähemalt 30 minutiks jälgimisele.', 'Stay for observation for at least 30 minutes after the injection.'], ['Hoia isiklik hädaabiplaan ja määratud erakorraline ravim käepärast ning kasuta seda nii, nagu arst on määranud.', 'Keep your personal emergency plan and prescribed emergency medicine to hand and use it as prescribed by your doctor.'], ['Pane hilinenud reaktsioonid kirja ja räägi neist enne järgmist süsti.', 'Record delayed reactions and report them before your next injection.']],
            'dont' => ['general.no_alcohol_24h', ['Ära lahku kliinikust enne jälgimisaja lõppu.', 'Do not leave the clinic before the observation period ends.'], ['Väldi süstipäeval tugevat trenni ja kuuma sauna.', 'Avoid strenuous exercise and hot saunas on the injection day.'], ['Ära jäta enne järgmist süsti teatamata hingamiskaebustest või haigestumisest.', 'Do not omit to report breathing symptoms or illness before the next injection.']],
            'hygiene' => [['Hoia süstekoht puhas ja väldi selle kratsimist või masseerimist.', 'Keep the injection site clean and avoid scratching or massaging it.']],
            'activity' => [['Vali süstipäeval rahulik tegevus ning järgi kliiniku koormusjuhiseid.', 'Choose gentle activities on the injection day and follow your clinic’s activity advice.']],
            'sleep' => [['Puhka vajaduse järgi; uute üldsümptomite korral otsi abi, mitte ära jää neid välja magama.', 'Rest as needed; seek help for new symptoms affecting your whole body rather than trying to sleep them off.']],
            'red_flag' => [
                ['Keele või kurgu turse, vilistava hingamise või üle keha leviva nõgestõvega halva enesetunde korral helista 112 ja järgi oma hädaabiplaani. Kliinikus teavita kohe personali.', 'Call 112 and follow your emergency plan for tongue or throat swelling, wheezing, or widespread hives with feeling unwell. If still at the clinic, alert staff immediately.'],
                ['Suure või järjest kasvava süstekoha turse korral võta samal päeval ühendust kliinikuga.', 'Contact the clinic the same day for large or progressively increasing swelling at the injection site.'],
            ],
        ],
    ]),
];
