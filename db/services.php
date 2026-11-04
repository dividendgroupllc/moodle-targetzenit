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
    'local_targetzenit_push_norms' => [
        'classname'   => 'local_targetzenit\\external\\push_norms',
        'methodname'  => 'execute',
        'description' => "Frappe'dan baholash normalarini qabul qiladi",
        'type'        => 'write',
        'capabilities' => 'local/targetzenit:push',
        'ajax'        => false,
    ],
    // Mobil ilovadan yozish. Panellardan farqli o'laroq BU funksiya
    // ro'yxatga qo'shilishi SHART — ilova uni to'g'ridan-to'g'ri chaqiradi.
    // `services` da `moodle_mobile_app` ko'rsatilgani uchun u servisga
    // AVTOMATIK qo'shiladi (Frappe funksiyalaridan farqi shunda).
    'local_targetzenit_mobile_save_record' => [
        'classname'   => 'local_targetzenit\\external\\mobile_save_record',
        'methodname'  => 'execute',
        'description' => "Mobil ilovadan yo'qlama va baho saqlash",
        'type'        => 'write',
        'capabilities' => 'local/targetzenit:teach',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_targetzenit_mobile_save_lesson' => [
        'classname'   => 'local_targetzenit\\external\\mobile_save_lesson',
        'methodname'  => 'execute',
        'description' => "Mobil ilovadan dars mavzusi va uy vazifasini saqlash",
        'type'        => 'write',
        'capabilities' => 'local/targetzenit:teach',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
