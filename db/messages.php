<?php
// Bildirishnoma manbalari.
//
// Fidbek yozilganda o'qituvchi Moodle'ning STANDART bildirishnoma tizimi
// orqali xabar oladi — pochta, mobil ilova va qo'ng'iroqcha. Yangi kanal
// yozilmaydi: tamoyil bo'yicha avval Moodle'ning o'z vositasi.

defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    'feedback' => [
        'capability' => 'local/targetzenit:teach',
    ],
    // MUROJAAT JAVOBI — o'quvchining akkauntiga boradi (ota-ona bolasining
    // logini bilan kiradi; Moodle'da ota-ona akkaunti yo'q). `capability`
    // ATAYLAB YO'Q: o'quvchida hech qanday maxsus ruxsat bo'lmaydi, shart
    // qo'yilsa bildirishnoma hech kimga yetmasdi.
    'murojaat' => [
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDOFF,
            'airnotifier' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
        ],
    ],
];
