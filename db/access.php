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
