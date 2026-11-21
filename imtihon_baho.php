<?php
// BAHOLASH EKRANI (ekran E, IMTIHON_ARXITEKTURA.md). Yozma (assign) uchun
// o'quvchilar ro'yxati va holati PORTAL qobig'ida; har o'quvchini baholash —
// YADRONING grader'iga (rubrika + editpdf + fikr) delegatsiya qilinadi.
// Test (quiz) uchun — avto natija + hisobotlar (grading/statistika/javoblar).

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use local_targetzenit\output\maket;

$cmid = required_param('cmid', PARAM_INT);
$cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
require_login($cm->course);
$context = context_module::instance($cm->id);
$t = fn(string $k, $a = null) => get_string($k, 'local_targetzenit', $a);

$modname = $cm->modname;
require_capability($modname === 'quiz' ? 'mod/quiz:grade' : 'mod/assign:grade', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/imtihon_baho.php', ['cmid' => $cmid]));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title($t('im_b_title'));
$PAGE->requires->css('/local/targetzenit/styles.css');

// Nizom bog'lanishi bo'lsa — «Ballarni chorakka import» tugmasi/handler.
$link = \local_targetzenit\imtihon::nizom_link($cmid);
if ($link && optional_param('import', 0, PARAM_INT) && confirm_sesskey()) {
    $res = \local_targetzenit\imtihon::import_ballar($cmid, (int) $USER->id);
    redirect($PAGE->url, $t('im_b_imported', (object) ['n' => $res['n'], 'jami' => $res['jami']]),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

maket::boshla('imtihon');
echo html_writer::start_div('tz-ishstoli-wrap');

echo html_writer::div(html_writer::link(new moodle_url('/local/targetzenit/imtihonlar.php'),
    '← ' . $t('im_nav'), ['class' => 'btn btn-sm btn-outline-secondary']), 'mb-3');
echo html_writer::tag('div', $t('im_b_title') . ' · ' . format_string($cm->name), ['class' => 'tzp-h1']);

if ($link) {
    $exam = \local_targetzenit\exam::get((int) $link->examid);
    echo html_writer::start_tag('form', ['method' => 'post',
        'action' => $PAGE->url->out(false), 'class' => 'tz-imp mb-3']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'import', 'value' => 1]);
    echo html_writer::tag('span',
        '🔗 ' . ($exam ? s($exam->turi . ' · ' . $exam->termname) : ''),
        ['class' => 'tz-spill tz-spill-g']) . ' ';
    echo html_writer::tag('button', $t('im_b_import'),
        ['type' => 'submit', 'class' => 'btn btn-sm btn-primary']);
    echo ' ' . html_writer::tag('span', $t('im_b_import_note'), ['class' => 'text-muted small']);
    echo html_writer::end_tag('form');
}

if ($modname === 'assign') {
    // ====================================================== YOZMA (assign)
    $aid = (int) $cm->instance;
    $coursectx = context_course::instance($cm->course);
    $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);

    $rows = $studentrole ? $DB->get_records_sql("
        SELECT u.id, u.firstname, u.lastname,
               sub.status AS substatus, sub.timemodified AS subtime, g.grade
          FROM {user} u
          JOIN {role_assignments} ra ON ra.userid = u.id
               AND ra.roleid = :rid AND ra.contextid = :ctxid
     LEFT JOIN {assign_submission} sub ON sub.assignment = :aid
               AND sub.userid = u.id AND sub.latest = 1
     LEFT JOIN {assign_grades} g ON g.assignment = :aid2 AND g.userid = u.id
         WHERE u.deleted = 0
      ORDER BY u.lastname, u.firstname",
        ['rid' => $studentrole, 'ctxid' => $coursectx->id, 'aid' => $aid, 'aid2' => $aid]) : [];

    $topshirdi = $baholandi = 0;
    foreach ($rows as $r) {
        if ($r->substatus === 'submitted') {
            $topshirdi++;
        }
        if ($r->grade !== null && (float) $r->grade >= 0) {
            $baholandi++;
        }
    }

    echo html_writer::tag('div', $t('im_b_sub', (object) [
        'topshirdi' => $topshirdi, 'baholandi' => $baholandi,
        'jami' => count($rows)]), ['class' => 'tzp-sub']);

    echo html_writer::div(html_writer::link(
        new moodle_url('/mod/assign/view.php', ['id' => $cmid, 'action' => 'grading']),
        $t('im_b_gradeall') . ' →', ['class' => 'btn btn-sm btn-outline-secondary mb-2']), '');

    echo html_writer::start_div('tzp-card p-0 tz-scroll');
    echo html_writer::start_tag('table', ['class' => 'tz-im-table', 'style' => 'min-width:560px']);
    echo html_writer::start_tag('tbody');
    if (!$rows) {
        echo html_writer::tag('tr', html_writer::tag('td',
            html_writer::div($t('im_b_empty'), 'tzp-empty'), ['colspan' => 4]));
    }
    foreach ($rows as $r) {
        $ism = fullname($r);
        $baholangan = ($r->grade !== null && (float) $r->grade >= 0);
        if ($baholangan) {
            $hol = html_writer::tag('span', $t('oqp_s_done'), ['class' => 'tz-spill tz-spill-g']);
            $mark = format_float((float) $r->grade, 1);
        } else if ($r->substatus === 'submitted') {
            $hol = html_writer::tag('span', $t('im_b_tograde'), ['class' => 'tz-spill tz-spill-a']);
            $mark = '—';
        } else if ($r->substatus === 'draft') {
            $hol = html_writer::tag('span', $t('im_b_draft'), ['class' => 'tz-spill tz-spill-wt']);
            $mark = '—';
        } else {
            $hol = html_writer::tag('span', $t('im_b_nosub'), ['class' => 'tz-spill tz-spill-r']);
            $mark = '—';
        }
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', html_writer::tag('b', s($ism)));
        echo html_writer::tag('td', $hol);
        echo html_writer::tag('td', $mark, ['class' => 'tz-nowrap']);
        echo html_writer::tag('td', html_writer::link(
            new moodle_url('/mod/assign/view.php',
                ['id' => $cmid, 'action' => 'grader', 'userid' => (int) $r->id]),
            $t('im_amal_grade') . ' →', ['class' => 'btn btn-sm btn-primary']),
            ['class' => 'tz-nowrap']);
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody') . html_writer::end_tag('table');
    echo html_writer::end_div();

    echo html_writer::div($t('im_b_rubric_note'), 'tzp-note');

} else {
    // ====================================================== TEST (quiz)
    echo html_writer::tag('div', $t('im_b_quiz_sub'), ['class' => 'tzp-sub']);
    $rep = fn(string $mode, string $lab, string $sub) => html_writer::div(
        html_writer::link(new moodle_url('/mod/quiz/report.php', ['id' => $cmid, 'mode' => $mode]),
            html_writer::tag('b', $t($lab)) . ' →')
        . html_writer::div($t($sub), 'tzp-sub'), 'tz-replink');

    echo html_writer::start_div('tzp-card');
    echo $rep('overview', 'im_b_r_grades', 'im_b_r_grades_s');
    echo $rep('grading', 'im_b_r_manual', 'im_b_r_manual_s');
    echo $rep('statistics', 'im_b_r_stats', 'im_b_r_stats_s');
    echo $rep('responses', 'im_b_r_resp', 'im_b_r_resp_s');
    echo html_writer::end_div();
    echo html_writer::div($t('im_b_quiz_note'), 'tzp-note');
}

echo html_writer::end_div();   // tz-ishstoli-wrap
maket::tugat();
