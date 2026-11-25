<?php

// DRAFT — must be reviewed by clinic medical staff before use.

$make = require __DIR__.'/_general.php';

return [
    $make([
        'slug' => 'iud-insertion',
        'name' => ['Emakasisese vahendi paigaldamine', 'IUD insertion'],
        'summary' => ['Emakasse paigaldatakse rasestumisvastane vahend ehk spiraal. Alguses võib esineda alakõhukrampe ja määrimist; kaitse algusaeg sõltub vahendist ning paigaldamise ajast.', 'A contraceptive device, often called a coil, is placed in the womb. Cramps and spotting may occur initially; when protection starts depends on the device and timing of insertion.'],
        'keywords' => ['spiraal, emakasisene vahend, hormoonspiraal, vaskspiraal, rasestumisvastane vahend, ESV', 'IUD, IUS, coil, intrauterine device, hormonal coil, copper coil'],
        'follow_up' => ['Kontroll võib toimuda umbes 4–6 nädala pärast, kui arst on selle määranud. Niitide muutuse, püsiva valu või raseduskahtluse korral võta kliinikuga varem ühendust.', 'A review may take place after about 4–6 weeks if your doctor has booked one. Contact the clinic sooner for a change in threads, persistent pain or possible pregnancy.'],
        'recovery' => ['Järgmised nädalad', 'Following weeks'],
        'steps' => [
            [['Puhka enesetunde järgi', 'Rest as you need'], ['Kerge krambitunne ja määrimine võivad esineda. Kui tunned pearinglust, istu või heida pikali ja teavita enne lahkumist ravimeeskonda.', 'Mild cramps and spotting may occur. If you feel dizzy, sit or lie down and tell the treatment team before leaving.']],
            [['Jälgi valu ja veritsust', 'Watch pain and bleeding'], ['Vaevused peaksid üldiselt leevenema, kuigi määrimine võib jätkuda. Tugevnev alakõhuvalu vajab kliinikuga ühenduse võtmist.', 'Symptoms should generally ease, although spotting may continue. Contact the clinic for increasing lower abdominal pain.']],
            [['Täpsusta rasestumisvastast kaitset', 'Confirm contraceptive protection'], ['Järgi täpselt lisakaitse kohta antud juhiseid. Spiraal ei kaitse sugulisel teel levivate nakkuste eest.', 'Follow your instructions about additional contraception exactly. A coil does not protect against sexually transmitted infections.']],
            [['Jälgi oma tavapärast rütmi', 'Watch your usual pattern'], ['Veritsuse muutused võivad kesta kauem kui esialgsed krambid. Kui muutus teeb muret, aruta seda kliinikuga.', 'Bleeding changes may last longer than the initial cramps. Discuss any worrying change with your clinic.']],
        ],
        'advice' => [
            'do' => ['general.rest_first_day', ['Hoia alles vahendi nimetus ja paigaldamise kuupäev.', 'Keep a record of the device name and insertion date.'], ['Küsi enne lahkumist, millal rasestumisvastane kaitse algab.', 'Ask when contraceptive protection starts before leaving.'], ['Õpi niite kontrollima ainult ravimeeskonna juhiste järgi.', 'Learn to check the threads only as shown by your treatment team.']],
            'dont' => [['Ära tõmba spiraali niite.', 'Do not pull the coil threads.'], ['Ära eelda, et rasestumisvastane kaitse algab alati kohe.', 'Do not assume contraceptive protection always starts immediately.'], ['Ära püüa vahendit ise tagasi lükata, kui tunned selle kõva osa.', 'Do not try to push the device back if you feel its hard part.'], ['Ära pea tugevat või süvenevat alakõhuvalu tavapäraseks paranemiseks.', 'Do not dismiss severe or increasing lower abdominal pain as routine recovery.']],
            'hygiene' => [['Pese välissuguelundeid õrnalt; ära loputa tuppe seestpoolt. Menstruaaltoodete kohta järgi kliiniku juhiseid.', 'Wash the external genital area gently; do not douche. Follow your clinic’s advice about menstrual products.']],
            'activity' => [['Naase tavategevuste juurde enesetunde järgi; seksuaalelu ja lisakaitse kohta järgi saadud juhiseid.', 'Return to usual activities as you feel able; follow your instructions about sex and additional contraception.']],
            'sleep' => [['Puhka endale mugavas asendis; spiraal ei vaja erilist magamisasendit.', 'Rest in a comfortable position; a coil does not require a special sleeping position.']],
            'red_flag' => [
                ['Tugeva või süveneva alakõhuvalu, halvalõhnalise vooluse või ebatavaliselt rohke veritsuse korral võta kohe ühendust kliinikuga.', 'Contact the clinic promptly for severe or increasing lower abdominal pain, offensive discharge or unusually heavy bleeding.'],
                ['Kui niidid kaovad või muutuvad, tunned kõva vahendit või kahtlustad rasedust, võta kiiresti ühendust kliinikuga ja kasuta kuni hindamiseni lisakaitset; kaitsmata vahekorra järel küsi kohe erakorralise rasestumisvastase abi kohta.', 'Contact the clinic promptly and use additional contraception until assessed if threads disappear or change, you feel the hard device or suspect pregnancy; after unprotected sex, seek advice about emergency contraception immediately.'],
            ],
        ],
    ]),
];
