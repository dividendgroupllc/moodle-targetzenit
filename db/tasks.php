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
];
