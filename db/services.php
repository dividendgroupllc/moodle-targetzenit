<?php
// Frappe uchun maxsus web service funksiyalari.
//
// DIQQAT: bu yerda `services` ro'yxati ataylab bo'sh — funksiya avtomatik
// biror servisga qo'shilmaydi. Mavjud "Frappe Sync" servisi qo'lda yaratilgan,
// unga bu funksiya ham qo'lda qo'shiladi (Server -> Web services ->
// External services -> Frappe Sync -> Functions).

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_targetzenit_push_timetable' => [
        'classname'   => 'local_targetzenit\external\push_timetable',
        'methodname'  => 'execute',
        'description' => "Frappe'dan dars jadvali va o'quv kalendarini qabul qiladi",
        'type'        => 'write',
        'capabilities' => 'local/targetzenit:push',
        'ajax'        => false,
    ],
    'local_targetzenit_push_payroll' => [
        'classname'   => 'local_targetzenit\\external\\push_payroll',
        'methodname'  => 'execute',
        'description' => "Frappe'dan oylik tabel ma'lumotini qabul qiladi",
        'type'        => 'write',
        'capabilities' => 'local/targetzenit:push',
        'ajax'        => false,
    ],
    'local_targetzenit_push_settlement' => [
        'classname'   => 'local_targetzenit\\external\\push_settlement',
        'methodname'  => 'execute',
        'description' => "Frappe'dan xodim bilan hisob-kitobni (oy kesimida) qabul qiladi",
        'type'        => 'write',
        'capabilities' => 'local/targetzenit:push',
        'ajax'        => false,
    ],
];
