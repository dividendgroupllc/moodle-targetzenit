<?php
// GURUH TUTORLARINI BIRIKTIRISH.
//
// Jadvalda ba'zi fanlar vakant — o'qituvchi tayinlanmagan. Bunday darslarni
// guruhning o'z tutori o'tadi, shuning uchun hisobot unga yozilishi kerak.
// Frappe'da guruh ↔ tutor bog'lanishi yo'q, shuning uchun u shu yerda
// yuritiladi (qoida: moliya ERP'da, dars jarayoni Moodle'da).

require(__DIR__ . '/../../config.php');

use local_targetzenit\fact;
use local_targetzenit\output\shell;
use local_targetzenit\timetable;
use local_targetzenit\tutor;

require_login();

$context = context_system::instance();
require_capability('local/targetzenit:viewteachers', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/tutorlar.php'));
shell::setup($PAGE, get_string('dir_tutors', 'local_targetzenit'));

$year = timetable::current_year();
if (!$year) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('timetable_empty', 'local_targetzenit'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$guruhlar = tutor::groups($year);
$nomzodlar = tutor::candidates($year);

// ------------------------------------------------------------------ SAQLASH
if (optional_param('saqla', 0, PARAM_INT) && confirm_sesskey()) {
    $tanlov = optional_param_array('tutor', [], PARAM_INT);
    $ozgardi = 0;

    foreach ($guruhlar as $gkey => $info) {
        // Forma kaliti sifatida guruh HASH'i ishlatiladi: `optional_param_array`
        // kalitlarda faqat [a-z0-9_-] ga ruxsat beradi va mos kelmaganini
        // JIMGINA tashlab yuboradi (jurnal setkasidagi bilan bir xil tuzoq).
        $fk = tutor::key($year, $gkey);
        if (!array_key_exists($fk, $tanlov)) {
            continue;
        }
        $yangi = (int) $tanlov[$fk];
        if ($yangi && !isset($nomzodlar[$yangi])) {
            continue;   // ro'yxatdan tashqari qiymat — qabul qilinmaydi
        }
        if ($yangi !== (int) $info['userid']) {
            tutor::set($year, $gkey, $info['nom'], $yangi);
            $ozgardi++;
        }
    }

    // Biriktirish o'zgarsa, vakant darslarning egasi ham o'zgaradi —
    // faktni qayta hisoblaymiz, aks holda panel eski holatni ko'rsatadi.
    if ($ozgardi) {
        $terms = timetable::terms($year);
        if ($terms) {
            \core_php_time_limit::raise(600);
            fact::refresh(min(array_map(fn($t) => $t->startdate, $terms)),
                          max(array_map(fn($t) => $t->enddate, $terms)));
        }
    }

    redirect(new moodle_url('/local/targetzenit/tutorlar.php'),
        get_string('dir_tutorsaved', 'local_targetzenit', $ozgardi), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
shell::start('tutorlar', get_string('dir_tutors', 'local_targetzenit'),
    get_string('dir_tutorshelp', 'local_targetzenit'));

if (!$nomzodlar) {
    echo $OUTPUT->notification(get_string('dir_tutornocand', 'local_targetzenit'), 'warning');
    shell::end();
    echo $OUTPUT->footer();
    exit;
}

$jami_vakant = 0;
$biriktirilmagan = 0;
foreach ($guruhlar as $g) {
    $jami_vakant += $g['vakant'];
    if ($g['vakant'] > 0 && !$g['userid']) {
        $biriktirilmagan++;
    }
}

echo html_writer::div(
    get_string('dir_tutorsum', 'local_targetzenit', (object) [
        'vakant' => $jami_vakant,
        'guruh' => $biriktirilmagan,
    ]), 'tz-dir-health tz-dir-h-' . ($biriktirilmagan ? 'warning' : 'serious'));

echo html_writer::start_tag('form', ['method' => 'post',
    'action' => (new moodle_url('/local/targetzenit/tutorlar.php'))->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'saqla', 'value' => 1]);

echo html_writer::start_div('tz-dir-card');
echo html_writer::start_div('tz-dir-tablewrap');
echo html_writer::start_tag('table', ['class' => 'tz-dir-table']);
echo html_writer::start_tag('thead') . html_writer::start_tag('tr');
foreach (['dir_colgroup', 'dir_colvacant', 'dir_colvacsubj', 'dir_coltutor'] as $k) {
    echo html_writer::tag('th', get_string($k, 'local_targetzenit'));
}
echo html_writer::end_tag('tr') . html_writer::end_tag('thead');

echo html_writer::start_tag('tbody');
foreach ($guruhlar as $gkey => $info) {
    $fk = tutor::key($year, $gkey);

    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', html_writer::tag('b', s($info['nom'])));

    echo html_writer::tag('td', $info['vakant']
        ? html_writer::tag('span', get_string('dir_nweek', 'local_targetzenit', $info['vakant']),
            ['class' => 'tz-dir-chip tz-dir-' . ($info['userid'] ? 'good' : 'critical')])
        : html_writer::tag('span', '—', ['class' => 'tz-dir-sub']));

    echo html_writer::tag('td',
        html_writer::tag('span', s(implode(', ', array_unique($info['fanlar']))),
            ['class' => 'tz-dir-sub']));

    echo html_writer::tag('td', html_writer::select(
        [0 => get_string('dir_tutornone', 'local_targetzenit')] + $nomzodlar,
        'tutor[' . $fk . ']', $info['userid'], false));

    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('tbody') . html_writer::end_tag('table');
echo html_writer::end_div();

echo html_writer::div(html_writer::empty_tag('input', [
    'type' => 'submit', 'class' => 'btn btn-primary',
    'value' => get_string('savechanges'),
]), 'mt-3');
echo html_writer::end_div();
echo html_writer::end_tag('form');

shell::end();
echo $OUTPUT->footer();
