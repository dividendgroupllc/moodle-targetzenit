<?php
// Bildirishnoma manbalari.
//
// Fidbek yozilganda o'qituvchi Moodle'ning STANDART bildirishnoma tizimi
// orqali xabar oladi — pochta, mobil ilova va qo'ng'iroqcha. Yangi kanal
// yozilmaydi: tamoyil bo'yicha avval Moodle'ning o'z vositasi.

// DIQQAT — KONSTANTALAR: `MESSAGE_DEFAULT_LOGGEDIN` va
// `MESSAGE_DEFAULT_LOGGEDOFF` Moodle 3.6 dan beri OLIB TASHLANGAN
// (lib/upgrade.txt 716-qator). Ularni ishlatish BUTUN saytning
// yangilanishini to'xtatadi: "Undefined constant" ->
// `message_update_providers()` yiqiladi va hech bir plagin yangilanmaydi
// (2026-11-06 da ikki deploy shu sababdan yiqildi). To'g'ri konstanta —
// `MESSAGE_DEFAULT_ENABLED`, yadro plaginlari ham shuni ishlatadi.

defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    'feedback' => [
        'capability' => 'local/targetzenit:teach',
    ],
    // MUROJAAT JAVOBI — o'quvchining akkauntiga boradi (ota-ona bolasining
    // logini bilan kiradi; Moodle'da ota-ona akkaunti yo'q). `capability`
    // ATAYLAB YO'Q: o'quvchida hech qanday maxsus ruxsat bo'lmaydi, shart
    // qo'yilsa bildirishnoma hech kimga yetmasdi.
    // O'QUVCHI SHARHI — o'qituvchi yozgan izoh o'quvchining akkauntiga
    // boradi (ota-ona bolasining logini bilan kiradi). `capability` ATAYLAB
    // YO'Q: o'quvchida hech qanday maxsus ruxsat bo'lmaydi, shart qo'yilsa
    // bildirishnoma hech kimga yetmasdi (murojaat bilan bir xil sabab).
    'sharh' => [
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
        ],
    ],
    'murojaat' => [
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
        ],
    ],
];
