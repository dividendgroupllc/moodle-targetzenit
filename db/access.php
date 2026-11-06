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

    // DIREKTOR PANELI: butun maktab o'qituvchilarining dars jarayonini ko'rish.
    //
    // Zavuch ham SHU ruxsat bilan yuradi va qamrovi CHEKLANMAYDI (qaror
    // 2026-10-29, DIREKTOR_PANELI.md 4.4) — shuning uchun kurs kategoriyasi
    // konteksti emas, tizim konteksti.
    'local/targetzenit:viewteachers' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],

    // Dars yoki o'qituvchi haqida fidbek yozish.
    'local/targetzenit:givefeedback' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],

    // Ma'muriy (o'qituvchiga ko'rinmaydigan) qaydni ko'rish.
    //
    // NEGA ALOHIDA RUXSAT: o'qituvchi o'zi haqida HAMMA narsani ko'radi (qaror
    // 2026-10-29) — yagona istisno shu toifa. Ruxsat ajratilmasa, istisnoni
    // amalda ajratib bo'lmaydi.
    'local/targetzenit:viewprivatefeedback' => [
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

    // MUROJAATLAR: ota-ona shikoyat/taklifi (MUROJAAT_ARXITEKTURA.md 4-bo'lim).
    //
    // `archetypes` ATAYLAB BO'SH: ikkisi ham hech kimga avtomatik tushmaydi,
    // admin qo'lda rolga biriktiradi. `manager` arxetipiga bog'lasak CEO ham
    // direktor murojaatlarini ko'rar edi va yo'nalish ajratishning ma'nosi
    // qolmasdi. Sayt admini (is_siteadmin) ikkisini ham ko'radi.
    'local/targetzenit:murojaatceo' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [],
    ],

    // Direktor VA ZAVUCH shu ruxsat bilan yuradi — o'quv-tarbiya masalalari
    // ikkisining ham ishi (direktor paneli qamrovi bilan bir xil mantiq).
    'local/targetzenit:murojaatdirektor' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [],
    ],
];
