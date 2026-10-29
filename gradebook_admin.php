<?php
// Gradebook integratsiyasi: holat, oxirgi xato va mavjud baholarni ko'chirish.
//
// NEGA KERAK: jurnalda qo'yilgan baho Moodle'ning standart gradebook'iga
// tushishi shart (o'quvchi uni Moodle "Grades" sahifasi va mobil ilovada
// ko'radi). Agar tushmasa — sabab shu sahifada ko'rinadi.

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_targetzenit\gradebook;

admin_externalpage_setup('local_targetzenit_gradebook');

$amal = optional_param('amal', '', PARAM_ALPHA);
$kursid = optional_param('courseid', 0, PARAM_INT);

$PAGE->set_title('Gradebook integratsiyasi');
$PAGE->set_heading('Gradebook integratsiyasi');
$url = new moodle_url('/local/targetzenit/gradebook_admin.php');

if ($amal === 'tozala') {
    require_sesskey();
    gradebook::xato_tozala();
    redirect($url, 'Xato jurnali tozalandi');
}

if ($amal === 'kochir') {
    require_sesskey();
    // Ko'p bo'lsa uzoq ketadi — vaqt chegarasini ko'taramiz.
    \core_php_time_limit::raise(600);
    $n = gradebook::backfill($kursid);
    $turi = $n['xato'] ? \core\output\notification::NOTIFY_WARNING
        : \core\output\notification::NOTIFY_SUCCESS;
    redirect($url, "Jami {$n['jami']} baho | ko'chirildi {$n['ok']} | xato {$n['xato']}"
        . " | qayta hisoblangan kurs: {$n['kurslar']}", null, $turi);
}

echo $OUTPUT->header();

$h = gradebook::holat();
$tab = new html_table();
$tab->head = ['Ko\'rsatkich', 'Soni'];
$tab->data = [
    ['Bizning jurnalda baho', $h['bizda_baho']],
    ['Moodle gradebook\'ida dars ustuni', $h['gradebook_ustun']],
    ['Moodle gradebook\'ida baho', $h['gradebook_baho']],
];
echo html_writer::table($tab);

if ($h['bizda_baho'] > $h['gradebook_baho']) {
    echo $OUTPUT->notification(
        'Gradebook\'da ' . ($h['bizda_baho'] - $h['gradebook_baho'])
        . ' ta baho yetishmaydi. "Ko\'chirish" tugmasi ularni o\'tkazadi.',
        \core\output\notification::NOTIFY_WARNING);
}

$xato = gradebook::oxirgi_xato();
if ($xato) {
    echo $OUTPUT->notification('Oxirgi xato (' . userdate($xato['vaqt'])
        . ", jami {$xato['soni']} marta):\n" . s($xato['matn']),
        \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->single_button(
        new moodle_url($url, ['amal' => 'tozala', 'sesskey' => sesskey()]),
        'Xato jurnalini tozalash', 'get');
}

echo $OUTPUT->single_button(
    new moodle_url($url, ['amal' => 'kochir', 'sesskey' => sesskey()]),
    'Mavjud baholarni gradebook\'ga ko\'chirish', 'get');

echo $OUTPUT->footer();
