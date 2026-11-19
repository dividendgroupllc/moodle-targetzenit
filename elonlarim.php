<?php
// E'LONLARIM — o'quvchi (ota-ona farzand akkaunti bilan) ko'radigan e'lonlar.
// Ochilganda «o'qildi» deb belgilanadi (muallif statistikasi uchun).

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use local_targetzenit\elon;

require_login();
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/elonlarim.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('elonim_nav', 'local_targetzenit'));
$PAGE->set_heading(get_string('elonim_nav', 'local_targetzenit'));

$royxat = elon::for_student((int) $USER->id);

// Ochilgani uchun hammasini «o'qildi» deb belgilaymiz.
foreach ($royxat as $e) {
    elon::korildi((int) $e->id, (int) $USER->id);
}

echo $OUTPUT->header();

if (!$royxat) {
    echo $OUTPUT->notification(get_string('elonim_none', 'local_targetzenit'), 'info');
    echo $OUTPUT->footer();
    exit;
}

foreach ($royxat as $e) {
    if ($e->turi === 'majlis') {
        $tegmatn = get_string('elon_turi_majlis', 'local_targetzenit');
        $tegcls = 'tz-elon-tmajlis';
    } else if ($e->scope === 'maktab') {
        $tegmatn = get_string('elon_school_tag', 'local_targetzenit');
        $tegcls = 'tz-elon-tmaktab';
    } else {
        $tegmatn = get_string('elon_group_tag', 'local_targetzenit');
        $tegcls = 'tz-elon-tguruh';
    }
    $muhim = ($e->turi === 'muhim')
        ? ' ' . html_writer::tag('span', get_string('elon_turi_muhim', 'local_targetzenit'),
            ['class' => 'tz-elon-teg tz-elon-tmuhim']) : '';
    $majlis = $e->turi === 'majlis' && $e->sana
        ? html_writer::div('📅 ' . userdate(strtotime($e->sana), '%d-%B')
            . ($e->vaqt ? ' ' . s($e->vaqt) : '')
            . ($e->xona ? ' · ' . s($e->xona) : ''), 'tz-elon-majlis-info')
        : '';
    echo html_writer::div(
        html_writer::div(
            html_writer::tag('span', s($tegmatn), ['class' => 'tz-elon-teg ' . $tegcls])
            . $muhim
            . ' ' . html_writer::tag('b', s($e->sarlavha))
            . html_writer::tag('span', '', ['style' => 'flex:1'])
            . html_writer::tag('span',
                userdate((int) $e->timecreated, '%d-%B %H:%M'),
                ['class' => 'text-muted small']), 'tz-elon-head')
        . ($e->matn ? html_writer::tag('p', s((string) $e->matn), ['class' => 'mt-1']) : '')
        . $majlis,
        'tz-elon-item tz-elon-item-student ' . ($e->turi === 'majlis' ? 'tz-elon-majlis-card' : ''));
}

echo $OUTPUT->footer();
