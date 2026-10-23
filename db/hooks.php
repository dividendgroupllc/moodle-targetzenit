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
];
