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
];
