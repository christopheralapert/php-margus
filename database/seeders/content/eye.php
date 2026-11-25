<?php

// DRAFT — must be reviewed by clinic medical staff before use.

$make = require __DIR__.'/_general.php';

return [
    $make([
        'slug' => 'cataract-surgery',
        'name' => ['Kataraktioperatsioon', 'Cataract surgery'],
        'summary' => ['Hägustunud silmalääts asendatakse kunstläätsega. Silm vajab paranemise ajal kaitset ja arsti määratud silmaravi.', 'The cloudy lens is replaced with an artificial lens. Your eye needs protection and the eye treatment prescribed by your doctor while it heals.'],
        'keywords' => ['katarakt, kaeoperatsioon, hallkae, silmalääts, silmalaats, läätsevahetus, laatsevahetus', 'cataract surgery, cloudy lens, lens replacement, eye operation'],
        'follow_up' => ['Kontrolli aeg sõltub sinu raviplaanist; tule esimestel päevadel või nädalatel kontrolli, kui arst on selle määranud. Äkilise nägemise muutuse korral otsi abi kohe.', 'The review timing depends on your care plan; attend within the first days or weeks if your doctor has booked one. Seek help immediately for a sudden change in vision.'],
        'recovery' => ['4–6 nädalat', '4–6 weeks'],
        'steps' => [
            [['Kaitse opereeritud silma', 'Protect the operated eye'], ['Puhka ja kasuta silmakaitset kliiniku juhiste järgi. Ära juhi autot protseduuripäeval.', 'Rest and use the eye shield as instructed by your clinic. Do not drive on the day of surgery.']],
            [['Jätka määratud silmaravi', 'Continue prescribed eye treatment'], ['Kasuta silmatilku nii, nagu arst on määranud. Kerge kipitus võib esineda, kuid tugevnev valu vajab kiiret hindamist.', 'Use eye drops as prescribed by your doctor. Mild grittiness may occur, but increasing pain needs urgent assessment.']],
            [['Naase rahulike tegevuste juurde', 'Resume gentle activities'], ['Lugeda ja ekraani vaadata võid enesetunde järgi. Väldi silma hõõrumist ja mustuse sattumist silma.', 'Read and use screens as you feel comfortable. Avoid rubbing the eye or getting dirt in it.']],
            [['Aruta nägemise taastumist', 'Discuss your recovering vision'], ['Nägemine võib paranemise ajal muutuda. Prillide ja tavakoormuse juurde naasmise aeg lepi kokku silmaarstiga.', 'Vision may fluctuate during healing. Agree with your eye specialist when to arrange glasses and resume your usual level of activity.']],
        ],
        'advice' => [
            'do' => ['general.rest_first_day', ['Pese käsi enne silmatilkade kasutamist.', 'Wash your hands before using eye drops.'], ['Kasuta silmakaitset nii, nagu kliinik juhendas.', 'Use the eye shield as shown by your clinic.'], ['Lase kellelgi end pärast operatsiooni koju saata.', 'Arrange for someone to accompany you home after surgery.']],
            'dont' => ['general.no_sauna_week1', ['Ära hõõru ega suru silma.', 'Do not rub or press your eye.'], ['Ära uju enne silmaarsti luba.', 'Do not swim until your eye specialist allows it.'], ['Ära juhi autot enne, kui nägemine vastab nõuetele ja silmaarst on seda lubanud.', 'Do not drive until your vision meets the required standard and your eye specialist allows it.']],
            'hygiene' => [['Hoia seep ja šampoon silmast eemal. Ära puuduta tilgapudeli otsaga silma ega nahka.', 'Keep soap and shampoo out of the eye. Do not touch your eye or skin with the drop bottle tip.']],
            'activity' => [['Väldi rasket tõstmist ja tugevat pingutust, kuni ravimeeskond lubab koormust suurendada.', 'Avoid heavy lifting and strenuous exertion until your treatment team allows you to increase activity.']],
            'sleep' => [['Kasuta öösel kaitset, kui see on määratud, ja väldi survet silmale.', 'Use the shield at night if prescribed and avoid pressure on the eye.']],
            'red_flag' => [['Tugeva silmavalu, äkilise nägemise halvenemise, uute valgussähvatuste või vaatevälja varju korral võta kohe ühendust silmakliinikuga; kui sa kliinikut kätte ei saa, mine erakorralise meditsiini osakonda.', 'Contact the eye clinic immediately for severe eye pain, sudden deterioration in vision, new flashes or a shadow in your vision; if the clinic is unavailable, go to an emergency department.']],
        ],
    ]),
];
