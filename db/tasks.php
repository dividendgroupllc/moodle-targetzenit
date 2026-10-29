<?php
// Rejali vazifalar.

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        // Har kuni 03:30 — muddati o'tgan audiolarni o'chirish
        'classname' => 'local_targetzenit\task\cleanup_audio',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '3',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        // Har 15 daqiqada — yozib olingan darslarni AI bilan tahlil qilish
        'classname' => 'local_targetzenit\task\analyse_audio',
        'blocking' => 0,
        'minute' => '*/15',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        // Har kuni 04:00 — o'qituvchilar ro'yxati va dars rejasi (slot)
        'classname' => 'local_targetzenit\\task\\slot_build',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '4',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        // Har 15 daqiqada — oxirgi hafta faktini yangilash
        'classname' => 'local_targetzenit\\task\\fact_refresh',
        'blocking' => 0,
        'minute' => '*/15',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        // Har kuni 04:30 — butun o'quv yili faktini qayta hisoblash.
        // slot_build'dan KEYIN: fakt slotlarga tayanadi.
        'classname' => 'local_targetzenit\\task\\fact_rebuild',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '4',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
