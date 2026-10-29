<?php
// Moodle mobil ilovasi uchun panellar ro'yxati ("site plugin").
//
// ILOVA BIZNING SAHIFALARIMIZNI CHIZMAYDI. U Ionic ilovasi: web-servis orqali
// TAYYOR HTML bo'lagini oladi va uni o'z komponentlari bilan ko'rsatadi.
// Shuning uchun har panel uchun `classes/output/mobile.php` da metod va
// `templates/` da Ionic shabloni bo'ladi.
//
// WEB-SERVIS RO'YXATIGA QO'LDA QO'SHISH SHART EMAS: ilova panellarni
// `tool_mobile_get_content` orqali oladi, u esa shu faylni o'zi o'qiydi.
// Qo'lda qo'shish faqat YOZISH funksiyalari uchun kerak bo'ladi (dars
// kokpiti bosqichida) — ular `db/services.php` da
// `'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE]` bilan e'lon qilinadi.
//
// DIQQAT: bu fayl o'zgarganda plagin versiyasi oshirilishi va keshlar
// tozalanishi SHART — `tool_mobile` plagin ro'yxatini keshlaydi.

defined('MOODLE_INTERNAL') || die();

$addons = [
    'local_targetzenit' => [
        'handlers' => [
            'targetzenit' => [
                // Asosiy menyuda (pastdagi yoki yon menyuda) element bo'lib chiqadi.
                'delegate' => 'CoreMainMenuDelegate',
                'method' => 'main',
                'displaydata' => [
                    'title' => 'app_title',
                    'icon' => 'fas-school',
                    'class' => '',
                ],
            ],
        ],
        // Ilova shu satrlarni o'zi yuklab oladi va foydalanuvchining tilida
        // ko'rsatadi. Uchta til `lang/tools/strings.py` dan keladi —
        // tarjima qayta yozilmaydi.
        'lang' => [
            ['app_title', 'local_targetzenit'],
            ['app_welcome', 'local_targetzenit'],
            ['app_ok', 'local_targetzenit'],
            ['app_soon', 'local_targetzenit'],
            ['app_openweb', 'local_targetzenit'],
            ['app_nolink', 'local_targetzenit'],
            ['timetable', 'local_targetzenit'],
            ['mygrades', 'local_targetzenit'],
            ['mypayroll', 'local_targetzenit'],
        ],
    ],
];
