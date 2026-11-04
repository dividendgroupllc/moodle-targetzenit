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
            // DIQQAT: "tzhub" (umumiy sahifa) OLIB TASHLANDI. U 0-bosqichda
            // ilova qatlami ishlayotganini tekshirish uchun qo'yilgan edi va
            // ichida veb sahifalarga havolalar bor edi. Endi har panel
            // alohida element bo'lib turibdi, ya'ni u faqat menyuni
            // to'ldirib turardi: ilova sig'magan elementlarni "More" ichiga
            // yashiradi, shuning uchun har ortiqcha element qolganlarini
            // topishni qiyinlashtiradi. Metod (`mobile::main`) kodda qoldi —
            // kerak bo'lsa qaytarish oson.
            'tzregister' => [
                'delegate' => 'CoreMainMenuDelegate',
                'method' => 'lesson_view',
                'displaydata' => [
                    'title' => 'app_register',
                    'icon' => 'fas-clipboard-check',
                    'class' => '',
                ],
            ],
            // Choraklik baholar — o'quvchi x chorak jadvali.
            'tztermgrades' => [
                'delegate' => 'CoreMainMenuDelegate',
                'method' => 'grades_view',
                'displaydata' => [
                    'title' => 'app_termgrades',
                    'icon' => 'fas-table',
                    'class' => '',
                ],
            ],
            // Chorak jurnali — butun sinf bitta setkada. Moodle ilovasida
            // o'qituvchi uchun "Grader report" yo'q, shuning uchun bu yagona
            // yo'l.
            'tzjournal' => [
                'delegate' => 'CoreMainMenuDelegate',
                'method' => 'journal_view',
                'displaydata' => [
                    'title' => 'app_journal',
                    'icon' => 'fas-th',
                    'class' => '',
                ],
            ],
            // Mening oyligim — veb sahifasining aksi, tuzilishi va tartibi
            // o'sha. Faqat oylik ma'lumoti bor xodimga ko'rinadi (sahifaning
            // o'zi tekshiradi, menyuda esa doim turadi: ilova menyuni
            // foydalanuvchiga qarab yashira olmaydi).
            'tzpayroll' => [
                'delegate' => 'CoreMainMenuDelegate',
                'method' => 'payroll_view',
                'displaydata' => [
                    'title' => 'mypayroll',
                    'icon' => 'fas-money-bill',
                    'class' => '',
                ],
            ],
            'tztimetable' => [
                'delegate' => 'CoreMainMenuDelegate',
                'method' => 'timetable_view',
                'displaydata' => [
                    'title' => 'timetable',
                    'icon' => 'fas-calendar',
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
            ['timetable_empty', 'local_targetzenit'],
            ['timetable_nolessons', 'local_targetzenit'],
            ['tt_noclass', 'local_targetzenit'],
            ['app_todaylessons', 'local_targetzenit'],
            ['app_register', 'local_targetzenit'],
            ['app_nolessontoday', 'local_targetzenit'],
            ['app_lesson', 'local_targetzenit'],
            ['app_savegrade', 'local_targetzenit'],
            ['app_saved', 'local_targetzenit'],
            ['app_attendancehint', 'local_targetzenit'],
            ['app_lessonsaved', 'local_targetzenit'],
            ['app_noteach', 'local_targetzenit'],
            ['app_nostudents', 'local_targetzenit'],
            ['grade', 'local_targetzenit'],
            ['att_present', 'local_targetzenit'],
            ['att_absent', 'local_targetzenit'],
            ['att_late', 'local_targetzenit'],
            ['att_excused', 'local_targetzenit'],
            ['app_termgrades', 'local_targetzenit'],
            ['app_nocourses', 'local_targetzenit'],
            ['app_nogradesyet', 'local_targetzenit'],
            ['app_termaverages', 'local_targetzenit'],
            ['app_journal', 'local_targetzenit'],
            ['app_journalhint', 'local_targetzenit'],
            ['app_noterm_now', 'local_targetzenit'],
            ['pr_breakdown', 'local_targetzenit'],
            ['pr_calchint', 'local_targetzenit'],
            ['pr_days', 'local_targetzenit'],
            ['pr_in', 'local_targetzenit'],
            ['pr_out', 'local_targetzenit'],
            ['pr_footnote', 'local_targetzenit'],
            ['st_title', 'local_targetzenit'],
            ['st_hint', 'local_targetzenit'],
            ['st_month', 'local_targetzenit'],
            ['st_salary', 'local_targetzenit'],
            ['st_accrued', 'local_targetzenit'],
            ['st_paid', 'local_targetzenit'],
            ['st_remaining', 'local_targetzenit'],
            ['st_status', 'local_targetzenit'],
        ],
    ],
];
