<?php

// DRAFT — must be reviewed by clinic medical staff before use.

$make = require __DIR__.'/_general.php';

return [
    $make([
        'slug' => 'knee-arthroscopy',
        'name' => ['Põlve artroskoopia', 'Knee arthroscopy'],
        'summary' => ['Põlveliigest uuritakse või ravitakse väikeste nahalõigete kaudu. Jalale toetumise ja harjutuste juhised sõltuvad sellest, mida operatsioonil tehti.', 'The knee joint is examined or treated through small skin incisions. Instructions for weight-bearing and exercises depend on what was done during surgery.'],
        'keywords' => ['põlve artroskoopia, polve artroskoopia, põlveoperatsioon, polveoperatsioon, menisk, liigesekaamera', 'knee arthroscopy, keyhole knee surgery, meniscus, knee operation'],
        'follow_up' => ['Haavakontroll toimub sageli 1–2 nädala jooksul, kui arst on selle määranud. Taastusravi ja edasised kontrollid sõltuvad operatsiooni sisust.', 'A wound check often takes place within 1–2 weeks if your doctor has booked one. Rehabilitation and further reviews depend on the surgery performed.'],
        'recovery' => ['Nädalad kuni kuud', 'Weeks to months'],
        'steps' => [
            [['Toeta ja puhka', 'Support and rest'], ['Hoia jalg puhates kõrgemal. Toetu jalale ja kasuta karke täpselt ravimeeskonna juhiste järgi.', 'Raise your leg while resting. Bear weight and use crutches exactly as instructed by your treatment team.']],
            [['Liiguta lubatud ulatuses', 'Move within your limits'], ['Tee ainult õpetatud harjutusi ja liiguta hüppeliigest, kui see on lubatud. Jälgi sidet ja varvaste tundlikkust.', 'Do only the exercises you were taught and move your ankle if allowed. Watch the dressing and sensation in your toes.']],
            [['Jätka taastusravi', 'Continue rehabilitation'], ['Järgi koormuspiiranguid ka siis, kui valu väheneb. Mine kokkulepitud haavakontrolli.', 'Keep to weight-bearing restrictions even if pain eases. Attend the arranged wound check.']],
            [['Suurenda koormust juhendamisel', 'Increase activity with guidance'], ['Taastumisaeg erineb sõltuvalt tehtud ravist. Tööle, autojuhtimise ja spordi juurde naasmine lepi kokku ravimeeskonnaga.', 'Recovery time varies with the treatment performed. Agree with your treatment team when to return to work, driving and sport.']],
        ],
        'advice' => [
            'do' => ['general.keep_wound_clean', 'general.rest_first_day', ['Kasuta karke, kui need on soovitatud, ja veendu, et oskad neid ohutult kasutada.', 'Use crutches if advised and make sure you can use them safely.'], ['Järgi sulle antud trombiennetuse ja harjutuste plaani.', 'Follow your individual clot prevention and exercise plan.']],
            'dont' => ['general.no_smoking_48h', 'general.no_alcohol_24h', 'general.no_sauna_week1', ['Ära ületa lubatud koormust ega pööra järsult toetatud põlve peal.', 'Do not exceed your allowed weight-bearing or twist sharply on the operated knee.']],
            'hygiene' => [['Hoia haavasidemed kuivad seni, kuni kliinik lubab pesemist; ära leota haavu.', 'Keep wound dressings dry until your clinic allows washing; do not soak the wounds.']],
            'activity' => [['Tee lühikesi lubatud liikumisi ja õpetatud harjutusi, vältides pikka liikumatust.', 'Take short permitted movements and do the exercises you were taught, avoiding prolonged immobility.']],
            'sleep' => [['Toeta jalga mugavalt ravimeeskonna õpetatud viisil.', 'Support your leg comfortably as shown by your treatment team.']],
            'red_flag' => [
                ['Kui säär muutub ühepoolselt valulikuks või tursub, otsi samal päeval kiiret arstiabi ja teavita kliinikut.', 'Seek urgent medical assessment the same day and inform the clinic if one calf becomes painful or swollen.'],
                ['Kui põlv muutub järjest kuumemaks ja valusamaks, haavast tuleb mäda või labajalg on külm või tuim, võta kohe ühendust kliinikuga.', 'Contact the clinic promptly if the knee becomes increasingly hot and painful, pus appears or your foot is cold or numb.'],
            ],
        ],
    ]),
];
