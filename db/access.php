<?php
// Plagin ruxsatlari.

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    // Frappe integratsiyasi uchun: jadval va kalendarni yuborish huquqi.
    // Bu ruxsat `Frappe Sync` roliga beriladi, boshqa hech kimga emas.
    'local/targetzenit:push' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [],
    ],

    // Dars kokpiti: mavzu yozish, yo'qlama qilish, baho qo'yish.
    // Kurs kontekstida tekshiriladi — o'qituvchi faqat O'Z kursida ishlaydi.
    'local/targetzenit:teach' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // Dars tahlilini KO'RISH — zavuch/direktor uchun (butun maktab bo'ylab).
    // O'qituvchi o'z darsining tahlilini `teach` ruxsati bilan ko'radi.
    'local/targetzenit:viewanalysis' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],

    // BOSHQALARNING oyligini ko'rish — direktor/moliya uchun. Maxfiy ma'lumot,
    // shuning uchun faqat manager arxetipida.
    'local/targetzenit:viewallpayroll' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],

    // Umumiy (butun maktab) dars jadvalini ko'rish — o'qituvchilar uchun.
    // O'quvchi faqat o'z sinfinikini ko'radi, bu ruxsat unga berilmaydi.
    'local/targetzenit:viewalltimetable' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];
