<?php
// O'quvchining DARS SAHIFASI: mavzu, uy vazifa, materiallar va o'z natijasi.
//
// Kokpit (dars.php) o'qituvchiniki; bu sahifa esa o'sha darsning O'QUVCHI
// ko'radigan tomoni (portal 2-bosqichi): "bugun nima o'tildi, uyga nima
// berildi, qaysi materiallarni ochishim mumkin, men qanday baholandim".
// Kirish nuqtasi — baholarim.php dagi uy vazifalar ro'yxati.
//
// XAVFSIZLIK: faqat o'z natijasi ko'rinadi ($USER), URL'dan userid olinmaydi
// (oylik.php naqshi). O'qituvchi/rahbariyat ham ochishi mumkin — ular natija
// qismida butun ro'yxatni emas, hech narsa ko'rmaydi (ularning ekrani kokpit).

require(__DIR__ . '/../../config.php');

use local_targetzenit\lesson;
use local_targetzenit\lessonres;

$lessonid = required_param('id', PARAM_INT);

$dars = $DB->get_record('local_targetzenit_lesson', ['id' => $lessonid],
    '*', MUST_EXIST);
$course = get_course((int) $dars->courseid);
require_login($course);
$context = context_course::instance($course->id);

$oquvchimi = is_enrolled($context, $USER, '', true)
    && !has_capability('local/targetzenit:teach', $context);
if (!$oquvchimi
        && !has_capability('local/targetzenit:teach', $context)
        && !has_capability('local/targetzenit:viewanalysis', context_system::instance())) {
    throw new moodle_exception('nopermissions', 'error', '',
        get_string('ds_title', 'local_targetzenit'));
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/darsim.php', ['id' => $lessonid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

// ------------------------------------------------------------------ SARLAVHA
echo html_writer::div(
    html_writer::tag('span',
        userdate(strtotime($dars->lessondate), '%A, %d-%B %Y'),
        ['class' => 'tz-dars-sana']) . ' · ' .
    html_writer::tag('span',
        get_string('ck_lessonno', 'local_targetzenit', (int) $dars->lessonno),
        ['class' => 'tz-dars-raqam']),
    'tz-dars-bosh mb-2');

echo html_writer::div(
    html_writer::link(new moodle_url('/local/targetzenit/baholarim.php'),
        '← ' . get_string('mygrades', 'local_targetzenit'),
        ['class' => 'btn btn-sm btn-outline-secondary']),
    'mb-3');

// ------------------------------------------------------- MAVZU VA UY VAZIFA
echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', get_string('topic', 'local_targetzenit'),
    ['class' => 'card-title h6']);
echo html_writer::tag('p',
    trim((string) $dars->topic) !== ''
        ? format_string($dars->topic)
        : html_writer::tag('span', get_string('ds_notopic', 'local_targetzenit'),
            ['class' => 'text-muted']));

echo html_writer::tag('h5', get_string('homework', 'local_targetzenit'),
    ['class' => 'card-title h6 mt-3']);
if (trim((string) $dars->homework) !== '') {
    echo html_writer::tag('p', format_string($dars->homework));
    // Fayl topshirish talab qilingan bo'lsa — STANDART assign sahifasiga.
    if (!empty($dars->assigncmid) && $oquvchimi) {
        echo html_writer::div(
            html_writer::link(new moodle_url('/mod/assign/view.php',
                ['id' => (int) $dars->assigncmid]),
                get_string('ds_submit', 'local_targetzenit'),
                ['class' => 'btn btn-sm btn-primary']),
            'mb-1');
    }
} else {
    echo html_writer::tag('p', get_string('ds_nohomework', 'local_targetzenit'),
        ['class' => 'text-muted']);
}
echo html_writer::end_div();
echo html_writer::end_div();

// ----------------------------------------------------------- MATERIALLAR
// Faqat `koringan=1` qatorlar — o'qituvchi yashirgani chiqmaydi.
$resurslar = lessonres::rows($lessonid, true);
echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', get_string('ds_resources', 'local_targetzenit'),
    ['class' => 'card-title h6']);
if ($resurslar) {
    echo html_writer::start_tag('ul', ['class' => 'list-unstyled tz-res-list mb-0']);
    foreach ($resurslar as $res) {
        $rurl = lessonres::url_for($res, $context);
        if (!$rurl) {
            continue;
        }
        echo html_writer::tag('li',
            html_writer::tag('span', s(lessonres::tur_belgisi($res, $context)),
                ['class' => 'badge text-bg-secondary tz-res-badge me-2'])
            . html_writer::link($rurl, s(lessonres::nom($res, $context)),
                ['target' => '_blank', 'rel' => 'noopener']),
            ['class' => 'mb-1']);
    }
    echo html_writer::end_tag('ul');
} else {
    echo html_writer::tag('div', get_string('ds_noresources', 'local_targetzenit'),
        ['class' => 'text-muted small']);
}
echo html_writer::end_div();
echo html_writer::end_div();

// ------------------------------------------------------------ MENING NATIJAM
if ($oquvchimi) {
    $y = $DB->get_record('local_targetzenit_record',
        ['lessonid' => $lessonid, 'userid' => (int) $USER->id]);

    echo html_writer::start_div('card mb-3');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('ds_myresult', 'local_targetzenit'),
        ['class' => 'card-title h6']);

    if ($y) {
        $qatorlar = [];
        $davomat = lesson::attend_label((string) $y->attendance);
        $qatorlar[] = [get_string('attendance', 'local_targetzenit'), $davomat];
        if ($y->grade !== null) {
            $qatorlar[] = [get_string('grade', 'local_targetzenit'), (string) $y->grade];
        }
        if ($y->hwstatus !== null) {
            $uv = get_string('hw_' . $y->hwstatus, 'local_targetzenit');
            if ($y->hwgrade !== null) {
                $uv .= ' · ' . $y->hwgrade;
            }
            $qatorlar[] = [get_string('ck_hwcol', 'local_targetzenit'), $uv];
        }
        if (trim((string) $y->comment) !== '') {
            $qatorlar[] = [get_string('comment', 'local_targetzenit'),
                format_string($y->comment)];
        }
        $t = new html_table();
        $t->attributes['class'] = 'generaltable tz-ds-natija';
        foreach ($qatorlar as $q) {
            $t->data[] = [html_writer::tag('b', $q[0]), $q[1]];
        }
        echo html_writer::table($t);
    } else {
        echo html_writer::tag('div', get_string('ds_norecord', 'local_targetzenit'),
            ['class' => 'text-muted']);
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
