<?php
// Ilgaklar (hooks) ro'yxati.

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        // Yuqoridagi asosiy menyuga "Dars jadvali" havolasini qo'shadi —
        // har sahifada ko'rinib turadi, boshqaruv panelidan ham bir bosishda.
        'hook' => core\hook\navigation\primary_extend::class,
        'callback' => 'local_targetzenit\hook_callbacks::primary_extend',
    ],
    [
        // Avatar menyusiga (yuqori o'ngda) "Mening oyligim" — odam shaxsiy
        // narsani aynan shu ro'yxatdan qidiradi. Faqat oylik ma'lumoti bor
        // xodimga ko'rinadi.
        'hook' => core_user\hook\extend_user_menu::class,
        'callback' => 'local_targetzenit\hook_callbacks::extend_user_menu',
    ],
];
