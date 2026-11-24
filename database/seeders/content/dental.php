<?php

// DRAFT — must be reviewed by clinic medical staff before use.

$make = require __DIR__.'/_general.php';

return [
    $make([
        'slug' => 'tooth-extraction',
        'name' => ['Hamba eemaldamine', 'Tooth extraction'],
        'summary' => ['Eemaldatud hamba asemele jääb haav. Kaitse haavas tekkivat verehüüvet ja järgi hambaarsti juhiseid.', 'Removing a tooth leaves a wound. Protect the blood clot that forms there and follow your dentist’s instructions.'],
        'keywords' => ['hamba eemaldamine, hambatõmbamine, hambatombamine, tarkusehammas, hamba väljatõmbamine, hamba valjatombamine', 'tooth extraction, tooth removal, wisdom tooth, pulled tooth'],
        'follow_up' => ['Tule kontrolli või õmbluste eemaldamisele tavaliselt 1–2 nädala jooksul, kui arst on selle määranud. Kui vaevused süvenevad, võta kliinikuga varem ühendust.', 'Attend a check-up or suture removal, usually within 1–2 weeks, if your doctor has booked one. Contact the clinic sooner if symptoms worsen.'],
        'recovery' => ['2–4 nädalat', '2–4 weeks'],
        'steps' => [
            [['Kaitse verehüüvet', 'Protect the clot'], ['Hoia marlit haaval nii, nagu hambaarst juhendas. Esimesel päeval ära loputa suud jõuliselt ega sülita.', 'Hold gauze over the socket as your dentist showed you. Do not rinse vigorously or spit on the first day.']],
            [['Hoolda suud õrnalt', 'Care for your mouth gently'], ['Alates järgmisest päevast puhasta hambaid ettevaatlikult. Turse võib alguses suureneda; jälgi, kas see hakkab seejärel taanduma.', 'From the next day, clean your teeth carefully. Swelling may increase initially; watch for it starting to settle afterwards.']],
            [['Jälgi paranemist', 'Watch your recovery'], ['Naase tavapärase toidu juurde tasapisi, kui närimine on mugav. Kui valu pärast kolmandat päeva suureneb, võta kliinikuga ühendust.', 'Return gradually to your usual food when chewing feels comfortable. Contact the clinic if pain increases after day three.']],
            [['Jätka hoolikat puhastamist', 'Continue gentle cleaning'], ['Igeme paranemine võib võtta mitu nädalat. Väldi haava torkimist ka siis, kui valu on möödunud.', 'The gum may take several weeks to heal. Avoid poking the socket even after the pain has settled.']],
        ],
        'advice' => [
            'do' => ['general.keep_wound_clean', 'general.rest_first_day', ['Veritsuse korral suru puhas marlitampoon kindlalt haavale.', 'If bleeding occurs, press clean gauze firmly onto the socket.'], ['Söö esialgu teise suupoolega.', 'Chew on the other side of your mouth at first.']],
            'dont' => ['general.no_smoking_48h', 'general.no_alcohol_24h', 'general.no_sauna_week1', ['Ära kasuta joogikõrt ega puutu haava keelega.', 'Do not use a straw or touch the socket with your tongue.']],
            'eating' => [['Vali pehme ja jahe või leige toit; oota söömisega, kuni tuimus on möödunud.', 'Choose soft, cool or lukewarm food; wait until numbness has worn off before eating.']],
            'hygiene' => [['Puhasta ülejäänud hambaid õrnalt ja väldi harjaga otse haava puudutamist.', 'Brush your other teeth gently and avoid brushing directly over the socket.']],
            'activity' => [['Väldi esimestel päevadel rasket trenni ja tõstmist.', 'Avoid strenuous exercise and heavy lifting during the first few days.']],
            'sleep' => [['Puhka esimesel ööl veidi kõrgema peaalusega.', 'Rest with your head slightly raised on the first night.']],
            'red_flag' => [['Kui veritsus ei peatu pärast 30 minutit kindlat survet marliga, võta kohe ühendust kliinikuga.', 'Contact the clinic immediately if bleeding does not stop after 30 minutes of firm pressure with gauze.']],
        ],
    ]),
    $make([
        'slug' => 'dental-implant',
        'name' => ['Hambaimplantaadi paigaldamine', 'Dental implant placement'],
        'summary' => ['Lõualuusse paigaldatakse puuduvat hammast asendava hamba tugi. Hoia operatsioonipiirkonda ja järgi sulle antud koormamisjuhiseid.', 'A support for a replacement tooth is placed in your jawbone. Protect the surgical area and follow your individual instructions about biting and chewing.'],
        'keywords' => ['hambaimplantaat, implantaat, kunstjuur, hamba istutamine, tehisjuur', 'dental implant, tooth implant, artificial tooth root, implant surgery'],
        'follow_up' => ['Haava kontroll toimub sageli 1–2 nädala jooksul, kui arst on selle määranud. Implantaadi edasised kontrollid ja hamba valmistamine lepitakse eraldi kokku.', 'A wound check often takes place within 1–2 weeks if your doctor has booked one. Further implant checks and fitting the tooth are arranged separately.'],
        'recovery' => ['Järgmised kuud', 'Following months'],
        'steps' => [
            [['Hoia operatsioonipiirkonda', 'Protect the surgical area'], ['Puhka ja väldi implantaadi piirkonnaga närimist. Kasuta külmakotti läbi riide lühikeste pausidega, kui kliinik on seda soovitanud.', 'Rest and avoid chewing over the implant. Use a cold pack wrapped in cloth with breaks if your clinic has recommended it.']],
            [['Jälgi turset', 'Monitor swelling'], ['Turse ja sinikas võivad alguses suureneda. Puhasta suud ettevaatlikult kliiniku juhiste järgi.', 'Swelling and bruising may increase initially. Clean your mouth carefully according to your clinic’s instructions.']],
            [['Kaitse õmblusi', 'Protect the sutures'], ['Ära venita huult haava vaatamiseks ega puuduta õmblusi. Mine kokkulepitud haavakontrolli.', 'Do not pull your lip to inspect the wound or touch the sutures. Attend the arranged wound check.']],
            [['Lase implantaadil kinnituda', 'Allow the implant to integrate'], ['Luu paranemine võtab kauem aega kui igeme paranemine. Koorma implantaati ainult ravimeeskonnaga kokku lepitud viisil.', 'Bone healing takes longer than gum healing. Put pressure on the implant only as agreed with your treatment team.']],
        ],
        'advice' => [
            'do' => ['general.keep_wound_clean', 'general.rest_first_day', ['Söö esialgu implantaadi vastaspoolega.', 'Chew on the opposite side to the implant at first.'], ['Järgi ajutise proteesi kandmise kohta antud juhiseid.', 'Follow the instructions about wearing any temporary denture.']],
            'dont' => ['general.no_smoking_48h', 'general.no_alcohol_24h', 'general.no_sauna_week1', ['Ära proovi implantaadi liikuvust ega näri selle kohal kõva toitu.', 'Do not test whether the implant moves or chew hard food over it.']],
            'eating' => [['Vali pehme leige toit ja väldi kõvasid toidutükke haava lähedal.', 'Choose soft, lukewarm food and keep hard pieces away from the wound.']],
            'hygiene' => [['Puhasta teisi hambaid tavapäraselt; operatsioonipiirkonnas järgi hambaarsti puhastamisjuhiseid.', 'Clean your other teeth as usual; follow your dentist’s cleaning instructions for the surgical area.']],
            'activity' => [['Väldi esimestel päevadel pingutust ja kummardamist.', 'Avoid exertion and bending down during the first few days.']],
            'sleep' => [['Maga alguses pea veidi kõrgemal ja väldi survet opereeritud poolele.', 'Sleep with your head slightly raised initially and avoid pressure on the operated side.']],
            'red_flag' => [['Kui turse või valu suureneb pärast kolmandat päeva, haavast tuleb mäda või implantaat tundub liikuvat, võta kohe ühendust kliinikuga.', 'Contact the clinic promptly if swelling or pain increases after day three, pus appears or the implant seems loose.']],
        ],
    ]),
];
