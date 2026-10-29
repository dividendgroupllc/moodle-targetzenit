<?php
// O'quvchining o'z baholari, davomati va uy vazifalari.
//
// Baholar Moodle gradebook'ida emas (chorak hisobi o'zbekcha mantiqda), shuning
// uchun Moodle'ning standart "Grades" sahifasi bo'sh. O'quvchi shu yerdan ko'radi.
//
// O'qituvchi/ma'mur `userid` bilan boshqa o'quvchining kartasini ham ocha oladi.

require(__DIR__ . '/../../config.php');

use local_targetzenit\lesson;
use local_targetzenit\student_view;
use local_targetzenit\timetable;

require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT);
$termidx = optional_param('term', -1, PARAM_INT);

$context = context_system::instance();

// O'zinikidan boshqasini ko'rish: ma'mur (tizim darajasida ruxsati bor) yoki
// o'sha o'quvchiga dars beradigan o'qituvchi. O'qituvchining roli KURS
// kontekstida berilgani uchun tizim darajasida tekshirish yetarli emas.
if ($userid != $USER->id) {
    $ruxsat = has_capability('local/targetzenit:viewalltimetable', $context);
    if (!$ruxsat) {
        foreach (enrol_get_users_courses($userid, true, 'id') as $c) {
            if (has_capability('local/targetzenit:teach', context_course::instance($c->id))) {
                $ruxsat = true;
                break;
            }
        }
    }
    if (!$ruxsat) {
        throw new moodle_exception('nopermissions', 'error', '', 'boshqa o\'quvchi kartasi');
    }
}
$owner = ($userid == $USER->id) ? $USER : core_user::get_user($userid, '*', MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/baholarim.php', ['userid' => $userid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('mygrades', 'local_targetzenit'));
$PAGE->set_heading(($userid == $USER->id)
    ? get_string('mygrades', 'local_targetzenit')
    : fullname($owner));

$year = timetable::current_year();
$terms = array_values(timetable::terms($year));

echo $OUTPUT->header();

if (!$terms) {
    echo $OUTPUT->notification(get_string('mg_noterms', 'local_targetzenit'), 'warn');
    echo $OUTPUT->footer();
    exit;
}

// Standart: bugungi kun qaysi chorakda
if ($termidx < 0 || !isset($terms[$termidx])) {
    $termidx = 0;
    $bugun = date('Y-m-d');
    foreach ($terms as $i => $t) {
        if ($bugun >= $t->startdate && $bugun <= $t->enddate) {
            $termidx = $i;
            break;
        }
    }
}
$term = $terms[$termidx];

$options = [];
foreach ($terms as $i => $t) {
    $options[$i] = $t->name;
}
$select = new single_select(
    new moodle_url('/local/targetzenit/baholarim.php', ['userid' => $userid]),
    'term', $options, $termidx);
$select->set_label(get_string('mg_term', 'local_targetzenit') . ': ');
echo html_writer::div($OUTPUT->render($select), 'mb-2');

// Asosiy manba — Moodle'ning STANDART baholar hisoboti. Bu sahifa esa
// qulaylik: baholar, davomat va uy vazifa bir joyda, chorak kesimida.
echo html_writer::div(
    html_writer::link(
        new moodle_url('/grade/report/overview/index.php', ['userid' => $userid]),
        get_string('mg_gradebooklink', 'local_targetzenit'),
        ['class' => 'btn btn-sm btn-outline-primary mr-2']) .
    html_writer::tag('span',
        get_string('mg_gradebookhint', 'local_targetzenit'),
        ['class' => 'text-muted small']),
    'mb-3'
);

$fanlar = student_view::grades((int) $userid, $term->startdate, $term->enddate);

if (!$fanlar) {
    echo $OUTPUT->notification(get_string('mg_nogrades', 'local_targetzenit'), 'info');
} else {
    $t = new html_table();
    $t->head = [
        get_string('subject', 'local_targetzenit'),
        get_string('mg_grades', 'local_targetzenit'),
        get_string('average', 'local_targetzenit'),
        get_string('mg_attendance', 'local_targetzenit'),
    ];
    $t->attributes['class'] = 'generaltable tz-baholarim';

    foreach ($fanlar as $cid => $v) {
        $kataklar = '';
        foreach ($v['items'] as $it) {
            $sana = userdate(strtotime($it->lessondate), '%d.%m');
            $izoh = $sana . ($it->topic ? ' · ' . format_string($it->topic) : '');

            if ($it->grade !== null && $it->grade !== '') {
                $sinf = $it->grade >= 7 ? 'ok' : ($it->grade >= 5 ? 'mid' : 'low');
                $kataklar .= html_writer::tag('span', $it->grade,
                    ['class' => 'tz-g tz-g-' . $sinf, 'title' => $izoh]);
            } else if ($it->attendance !== 'present') {
                $belgi = \local_targetzenit\journal::CODES_BACK[$it->attendance] ?? '';
                $kataklar .= html_writer::tag('span', $belgi,
                    ['class' => 'tz-g tz-g-yoq', 'title' => $izoh]);
            }
        }
        if ($kataklar === '') {
            $kataklar = html_writer::tag('span',
                get_string('mg_nograde', 'local_targetzenit'),
                ['class' => 'text-muted small']);
        }

        $d = $v['davomat'];
        $davomat = $d['absent'] || $d['excused'] || $d['late']
            ? trim(
                ($d['absent']
                    ? get_string('mg_absent', 'local_targetzenit', $d['absent']) . '  ' : '') .
                ($d['excused']
                    ? get_string('mg_excused', 'local_targetzenit', $d['excused']) . '  ' : '') .
                ($d['late']
                    ? get_string('mg_late', 'local_targetzenit', $d['late']) : ''))
            : html_writer::tag('span',
                get_string('mg_full', 'local_targetzenit'), ['class' => 'text-success']);

        $t->data[] = [
            html_writer::tag('strong', format_string($v['course']->fullname ?? '')),
            html_writer::div($kataklar, 'tz-baho-qator'),
            $v['ortacha'] !== null
                ? html_writer::tag('strong', $v['ortacha'], ['class' => 'tz-ortacha'])
                : '',
            html_writer::tag('span', $davomat, ['class' => 'small']),
        ];
    }
    echo html_writer::div(html_writer::table($t), 'tz-scroll');
    echo html_writer::tag('p',
        get_string('mg_hint', 'local_targetzenit'),
        ['class' => 'text-muted small']);
}

// ---------------------------------------------------------------- Uy vazifa
$vazifalar = student_view::homework((int) $userid, $year, 7);
echo html_writer::tag('h4', get_string('mg_homework', 'local_targetzenit'),
    ['class' => 'mt-4']);

if (!$vazifalar) {
    echo $OUTPUT->notification(get_string('mg_nohomework', 'local_targetzenit'), 'info');
} else {
    echo html_writer::tag('p',
        get_string('mg_homeworkhint', 'local_targetzenit'),
        ['class' => 'text-muted small']);
    $t = new html_table();
    $t->head = [
        get_string('mg_date', 'local_targetzenit'),
        get_string('subject', 'local_targetzenit'),
        get_string('topic', 'local_targetzenit'),
        get_string('homework', 'local_targetzenit'),
    ];
    $t->attributes['class'] = 'generaltable';
    $bugun = date('Y-m-d');
    foreach ($vazifalar as $v) {
        $sana = userdate(strtotime($v->lessondate), '%d.%m');
        $t->data[] = [
            ($v->lessondate === $bugun)
                ? html_writer::tag('strong',
                    $sana . get_string('tt_today_suffix', 'local_targetzenit'))
                : $sana,
            format_string($v->coursename),
            format_string($v->topic ?? ''),
            format_string($v->homework),
        ];
    }
    echo html_writer::div(html_writer::table($t), 'tz-scroll');
}

echo $OUTPUT->footer();
