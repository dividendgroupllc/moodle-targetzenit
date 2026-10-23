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
];
