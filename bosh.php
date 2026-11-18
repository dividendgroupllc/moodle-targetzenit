<?php
// ISH STOLI — o'qituvchi portalining bosh sahifasi (TO'LIQ MAKET QOBIG'IDA).
//
// QAROR TARIXI (2026-11-18, uch bosqich): (1) alohida sahifa → menyuda ikkita
// dashboard chalg'itdi; (2) /my ichiga qo'shish → Moodle qobig'i baribir
// ko'rinib turdi; (3) YAKUNIY: o'qituvchi Moodle UIni UMUMAN ko'rmaydi —
// embedded layout + maket qobig'i (output\maket), /my dan o'qituvchi shu
// yerga yo'naltiriladi (hook_callbacks::before_http_headers). Moodle orqada:
// sessiya, ruxsat, gradebook, assign — hammasi o'zinikida ishlayveradi.

require(__DIR__ . '/../../config.php');

use local_targetzenit\output\ishstoli;
use local_targetzenit\output\maket;
use local_targetzenit\teacher;

require_login();
$context = context_system::instance();
$PAGE->set_context($context);

if (!teacher::has_any((int) $USER->id)) {
    // Dars o'tmaydiganga bu portal emas — Moodle'ning o'z dashboard'iga.
    redirect(new moodle_url('/my/'));
}

$PAGE->set_url(new moodle_url('/local/targetzenit/bosh.php'));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('bosh_nav', 'local_targetzenit'));
$PAGE->requires->css('/local/targetzenit/styles.css');

maket::boshla('bosh');
echo ishstoli::render();
maket::tugat();
