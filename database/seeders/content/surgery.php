<?php

// DRAFT — must be reviewed by clinic medical staff before use.

$make = require __DIR__.'/_general.php';

return [
    $make([
        'slug' => 'wound-suturing',
        'name' => ['Haava õmblemine', 'Wound suturing'],
        'summary' => ['Õmblused hoiavad haavaservi koos, kuni kude paraneb. Hoia haava puhtana ja kaitse seda venituse eest.', 'Sutures hold the wound edges together while the tissue heals. Keep the wound clean and protect it from stretching.'],
        'keywords' => ['haava õmblemine, haava omblemine, õmblused, omblused, haav, lõikehaav, loikehaav', 'wound suturing, stitches, sutures, cut, laceration'],
        'follow_up' => ['Haavakontroll ja õmbluste eemaldamine toimuvad sageli 1–2 nädala jooksul, kui arst on selle määranud; aeg sõltub haava asukohast. Küsi enne lahkumist täpset kontrolliaega.', 'Wound review and suture removal often take place within 1–2 weeks if your doctor has booked them; timing depends on the wound’s location. Ask for the specific review date before leaving.'],
        'recovery' => ['2–4 nädalat', '2–4 weeks'],
        'steps' => [
            [['Hoia esmast sidet', 'Protect the initial dressing'], ['Järgi kliiniku juhiseid esmase sideme kohta. Jäsemehaava korral hoia jäset võimaluse korral kõrgemal.', 'Follow the clinic’s instructions about the initial dressing. If the wound is on a limb, keep it raised when possible.']],
            [['Vaheta sidet hoolikalt', 'Change dressings carefully'], ['Vaheta märg või määrdunud side. Ära sikuta haava külge kuivanud sidet; küsi vajaduse korral kliinikust nõu.', 'Replace a wet or dirty dressing. Do not pull off a dressing stuck to the wound; ask the clinic for advice if needed.']],
            [['Kaitse õmblusi', 'Protect the sutures'], ['Väldi haava venitamist ja hõõrdumist. Jälgi, kas punetus, turse või valu suureneb.', 'Avoid stretching or rubbing the wound. Watch for increasing redness, swelling or pain.']],
            [['Hoia ka sulgunud haava', 'Protect the closed wound too'], ['Pärast õmbluste eemaldamist võib haav olla veel nõrk. Suurenda koormust ravimeeskonna juhiste järgi.', 'The wound may still be fragile after suture removal. Increase activity according to your treatment team’s advice.']],
        ],
        'advice' => [
            'do' => ['general.keep_wound_clean', 'general.rest_first_day', ['Pese käsi enne ja pärast sideme vahetamist.', 'Wash your hands before and after dressing changes.'], ['Küsi, kas sinu teetanusekaitse vajab ülevaatamist.', 'Ask whether your tetanus protection needs reviewing.']],
            'dont' => ['general.no_smoking_48h', 'general.no_alcohol_24h', 'general.no_sauna_week1', ['Ära eemalda õmblusi ise ega tõmba koorikut ära.', 'Do not remove sutures yourself or pull off the scab.']],
            'hygiene' => [['Pesemise alustamise aeg lepi kliinikuga kokku. Ära leota haava enne selle sulgumist.', 'Agree with the clinic when to start washing. Do not soak the wound before it has closed.']],
            'activity' => [['Väldi tegevusi, mis tõmbavad haava lahti või määrivad sidet.', 'Avoid activities that pull the wound apart or soil the dressing.']],
            'sleep' => [['Hoia haav magades surve ja hõõrdumise eest kaitstuna.', 'Protect the wound from pressure and rubbing while sleeping.']],
            'red_flag' => [['Leviv punetus, mäda, uus tuimus või haavaservade avanemine vajab samal päeval kliinikuga ühenduse võtmist.', 'Contact the clinic the same day for spreading redness, pus, new numbness or opening of the wound edges.']],
        ],
    ]),
    $make([
        'slug' => 'minor-burn-dressing',
        'name' => ['Väikese põletushaava sidumine', 'Minor burn dressing'],
        'summary' => ['Väikese põletushaava side kaitseb kahjustatud nahka. Järgi sideme vahetamise plaani; põletuse sügavus ja ulatus võivad vajada korduvat hindamist.', 'A dressing protects skin damaged by a minor burn. Follow your dressing change plan; the depth and extent of a burn may need reassessment.'],
        'keywords' => ['põletus, poletus, põletushaav, poletushaav, kuumakahjustus, vill, sidumine', 'minor burn, scald, burn dressing, blister, burnt skin'],
        'follow_up' => ['Põletushaava hinnatakse sageli uuesti lähipäevil, kui arst on selle määranud. Kui side saab märjaks või valu süveneb, võta kliinikuga varem ühendust.', 'A burn is often reassessed within the next few days if your doctor has booked a review. Contact the clinic sooner if the dressing gets wet or pain worsens.'],
        'recovery' => ['Umbes 2 nädalat', 'Around 2 weeks'],
        'steps' => [
            [['Kaitse põletushaava', 'Protect the burn'], ['Hoia pandud side puhas ja kuiv vastavalt kliiniku juhistele. Eemalda kahjustatud jäsemelt ehted, kui need tulevad kergesti ära.', 'Keep the applied dressing clean and dry as instructed by your clinic. Remove jewellery from the affected limb if it comes off easily.']],
            [['Mine vajaduse korral kontrolli', 'Attend reassessment if arranged'], ['Põletushaava välimus võib muutuda. Lase seda hinnata kokkulepitud ajal ja ära ava sidet pelgalt vaatamiseks.', 'The burn’s appearance can change. Have it checked at the agreed time and do not open the dressing just to look.']],
            [['Jälgi paranemist', 'Watch healing'], ['Jätka sidumist ravimeeskonna juhiste järgi. Liiguta lähedal asuvaid liigeseid õrnalt, kui see on lubatud.', 'Continue dressings as advised by your treatment team. Gently move nearby joints if allowed.']],
            [['Kaitse uut nahka', 'Protect the new skin'], ['Kui haav pole umbes 2 nädalaga sulgunud, küsi uut hinnangut. Sulgunud nahka kaitse päikese ja hõõrdumise eest.', 'Seek reassessment if the wound has not closed after about 2 weeks. Protect closed skin from sunlight and rubbing.']],
        ],
        'advice' => [
            'do' => ['general.keep_wound_clean', 'general.rest_first_day', ['Hoia põletatud jäset võimaluse korral kõrgemal.', 'Keep the affected limb raised when possible.'], ['Võta ühendust kliinikuga, kui side libiseb ära või märgub.', 'Contact the clinic if the dressing slips off or becomes wet.']],
            'dont' => [['Ära pane põletusele jääd.', 'Do not put ice on the burn.'], ['Ära määri haavale võid, õli ega koduseid segusid.', 'Do not put butter, oil or home-made mixtures on the wound.'], ['Ära torka ville katki.', 'Do not pierce blisters.'], ['Ära tõmba naha külge kleepunud sidet jõuga ära.', 'Do not force off a dressing stuck to the skin.']],
            'hygiene' => [['Pese käsi enne sideme puudutamist. Vaheta sidet ainult saadud juhiste järgi.', 'Wash your hands before touching the dressing. Change it only as instructed.']],
            'activity' => [['Kaitse sidet mustuse eest ja väldi liigutusi, mis haava hõõruvad.', 'Protect the dressing from dirt and avoid movements that rub the wound.']],
            'sleep' => [['Toeta põletatud jäse mugavalt, vältides survet haavale.', 'Support the affected limb comfortably, avoiding pressure on the wound.']],
            'red_flag' => [['Kui punetus levib, eritis haiseb, valu suureneb või sõrmed või varbad muutuvad külmaks ja tuimaks, võta kohe ühendust kliinikuga.', 'Contact the clinic promptly if redness spreads, discharge smells unpleasant, pain increases or fingers or toes become cold and numb.']],
        ],
    ]),
];
