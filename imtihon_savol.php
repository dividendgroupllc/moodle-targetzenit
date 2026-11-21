<?php
// SAVOL MUHARRIRI (ekran C, IMTIHON_ARXITEKTURA.md). Test (quiz) uchun
// savollar ro'yxati PORTAL qobig'ida; savol qo'shish/tahrirlash YADRONING
// sinalgan oqimiga (addquestion chooser — 17 tur, bank, import) delegatsiya
// qilinadi va returnurl bilan shu sahifaga qaytadi. Mexanikani yozmaymiz.

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use local_targetzenit\output\maket;

// Modal (iframe) ichida savol saqlangach yadro shu returnurl ga qaytadi.
// Ba'zan query (cmid/popup) yo'qoladi — shuning uchun popup YOKI cmid yo'q
// bo'lsa, faqat SIGNAL berib modalni yopamiz; ota sahifa o'zidagi cmid bilan
// ro'yxatni yangilaydi. `required_param` dan OLDIN turishi shart.
$cmid = optional_param('cmid', 0, PARAM_INT);
if (optional_param('popup', 0, PARAM_INT) || !$cmid) {
    require_login();
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><body style="font:14px system-ui;padding:24px;color:#15803d">'
        . s(get_string('im_s_saved', 'local_targetzenit'))
        . '<script>if(window.parent){window.parent.postMessage("tz-q-saved","*");}</script></body>';
    exit;
}

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
require_login($cm->course, false, $cm);
$modcontext = context_module::instance($cm->id);
require_capability('mod/quiz:manage', $modcontext);
$t = fn(string $k, $a = null) => get_string($k, 'local_targetzenit', $a);

$PAGE->set_context($modcontext);
$PAGE->set_url(new moodle_url('/local/targetzenit/imtihon_savol.php', ['cmid' => $cmid]));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title($t('im_s_title'));
$PAGE->requires->css('/local/targetzenit/styles.css');

$quizobj = \mod_quiz\quiz_settings::create_for_cmid($cmid);
$structure = \mod_quiz\structure::create_for_quiz($quizobj);
$quiz = $quizobj->get_quiz();

// Savol qo'shish oqimi uchun default kategoriya (yadro: modul konteksti banki).
$cat = question_get_default_category($modcontext->id, true);
// returnurl = shu sahifa + popup=1 — saqlangach modalga signal beradi.
$returnurl = new moodle_url('/local/targetzenit/imtihon_savol.php',
    ['cmid' => $cmid, 'popup' => 1]);

$addurl = new moodle_url('/question/bank/editquestion/addquestion.php', [
    'cmid' => $cmid, 'category' => $cat ? $cat->id : 0,
    'addonpage' => 0, 'appendqnumstring' => 'addquestion',
    'returnurl' => $returnurl->out_as_local_url(false)]);
$editorurl = new moodle_url('/mod/quiz/edit.php', ['cmid' => $cmid]);
$importurl = new moodle_url('/question/bank/importquestions/import.php',
    ['courseid' => $cm->course, 'cat' => ($cat ? $cat->id . ',' . $modcontext->id : '')]);

maket::boshla('imtihon');
echo html_writer::start_div('tz-ishstoli-wrap');

echo html_writer::div(html_writer::link(new moodle_url('/local/targetzenit/imtihonlar.php'),
    '← ' . $t('im_nav'), ['class' => 'btn btn-sm btn-outline-secondary']), 'mb-3');
echo html_writer::tag('div', $t('im_s_title') . ' · ' . format_string($quiz->name), ['class' => 'tzp-h1']);
echo html_writer::tag('div', $t('im_s_sub'), ['class' => 'tzp-sub']);

// Qadamlar (stepper)
echo html_writer::start_div('tz-step');
foreach ([['✓', 'im_w_type', 'done'], ['✓', 'im_w_main', 'done'], ['✓', 'im_w_settings', 'done'],
          ['4', 'im_s_step', 'on'], ['5', 'im_s_publish', '']] as [$n, $lab, $cls]) {
    echo html_writer::div(html_writer::tag('span', $n, ['class' => 'tz-step-n'])
        . $t($lab), 'tz-step-i ' . $cls);
}
echo html_writer::end_div();

echo html_writer::start_div('tz-oqp-cols');

// ===================== CHAP: qo'shish + ro'yxat
echo html_writer::start_div('tz-oqp-main');

// Qo'shish usullari
echo html_writer::start_div('tzp-card');
echo html_writer::tag('div', $t('im_s_add'), ['class' => 'tzp-h3']);
echo html_writer::start_div('tz-addrow');
echo html_writer::tag('button', '+ ' . $t('im_s_new'),
    ['type' => 'button', 'class' => 'btn btn-primary btn-sm tz-qopen',
     'data-url' => $addurl->out(false)]);
echo html_writer::tag('button', $t('im_s_bank'),
    ['type' => 'button', 'class' => 'btn btn-sm btn-outline-secondary tz-qopen',
     'data-url' => $editorurl->out(false)]);
echo html_writer::tag('button', $t('im_s_import'),
    ['type' => 'button', 'class' => 'btn btn-sm btn-outline-secondary tz-qopen',
     'data-url' => $importurl->out(false)]);
echo html_writer::end_div();
echo html_writer::div($t('im_s_types'), 'tzp-note');
echo html_writer::end_div();

// Savollar ro'yxati
$slots = $structure->get_slots();
$jami = 0;
$ball = 0.0;
echo html_writer::start_div('tzp-card p-0 tz-scroll');
echo html_writer::start_tag('table', ['class' => 'tz-im-table', 'style' => 'min-width:520px']);
echo html_writer::start_tag('tbody');
if (!$slots) {
    echo html_writer::tag('tr', html_writer::tag('td',
        html_writer::div($t('im_s_empty'), 'tzp-empty'), ['colspan' => 4]));
} else {
    foreach ($slots as $slot) {
        $jami++;
        $sn = (int) $slot->slot;
        $qtype = $structure->get_question_type_for_slot($sn);
        $nom = $structure->get_question_name_in_slot($sn);
        $maxmark = (float) ($slot->maxmark ?? 0);
        $ball += $maxmark;
        $turnomi = ($qtype && get_string_manager()->string_exists('pluginname', 'qtype_' . $qtype))
            ? get_string('pluginname', 'qtype_' . $qtype) : $qtype;
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', html_writer::tag('span', (string) $sn, ['class' => 'tz-qnum']),
            ['style' => 'width:34px']);
        echo html_writer::tag('td',
            html_writer::tag('span', s((string) $turnomi), ['class' => 'tz-qbadge'])
            . ' ' . s((string) $nom));
        echo html_writer::tag('td', format_float($maxmark, 2) . ' ' . $t('im_s_ball'),
            ['class' => 'tz-nowrap']);
        echo html_writer::tag('td', html_writer::link($editorurl,
            $t('im_s_edit'), ['class' => 'text-muted']), ['class' => 'tz-nowrap']);
        echo html_writer::end_tag('tr');
    }
}
echo html_writer::end_tag('tbody') . html_writer::end_tag('table');
echo html_writer::end_div();
echo html_writer::end_div();   // tz-oqp-main

// ===================== O'NG: xulosa
echo html_writer::start_div('tz-oqp-side');
echo html_writer::start_div('tzp-card');
echo html_writer::tag('div', $t('im_s_summary'), ['class' => 'tzp-h3']);
echo html_writer::div(html_writer::tag('span', $t('im_s_qcount'))
    . html_writer::tag('b', (string) $jami), 'tz-kv');
echo html_writer::div(html_writer::tag('span', $t('im_s_total'))
    . html_writer::tag('b', format_float($ball, 2)), 'tz-kv');
echo html_writer::end_div();

echo html_writer::start_div('tzp-card');
echo html_writer::tag('div', $t('im_s_ready'), ['class' => 'tzp-h3']);
echo html_writer::div($t('im_s_ready_note'), 'tzp-sub');
echo html_writer::link(new moodle_url('/mod/quiz/view.php', ['id' => $cmid]),
    $t('im_s_preview') . ' →', ['class' => 'btn btn-sm btn-outline-secondary mt-2']);
echo ' ';
echo html_writer::link(new moodle_url('/local/targetzenit/imtihonlar.php'),
    $t('im_s_done') . ' →', ['class' => 'btn btn-sm btn-primary mt-2']);
echo html_writer::end_div();
echo html_writer::end_div();   // tz-oqp-side

echo html_writer::end_div();   // tz-oqp-cols
echo html_writer::end_div();   // tz-ishstoli-wrap

// ---- Modal oyna: savol qo'shish/bank/import yadro formasi iframe ICHIDA.
echo html_writer::start_div('tz-qmodal', ['id' => 'tz-qmodal', 'hidden' => 'hidden']);
echo html_writer::start_div('tz-qmodal-box');
echo html_writer::div(
    html_writer::tag('b', $t('im_s_add'))
    . html_writer::tag('button', '✕', ['type' => 'button', 'id' => 'tz-qclose',
        'class' => 'tz-qclose', 'title' => get_string('close', 'editor')]),
    'tz-qmodal-head');
echo html_writer::tag('iframe', '',
    ['id' => 'tz-qframe', 'class' => 'tz-qframe', 'src' => 'about:blank']);
echo html_writer::end_div();
echo html_writer::end_div();

$PAGE->requires->js_amd_inline("
    require([], function() {
        var modal = document.getElementById('tz-qmodal');
        var frame = document.getElementById('tz-qframe');
        function och(url) {
            frame.src = url;
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
        }
        function yop(yangila) {
            modal.hidden = true;
            frame.src = 'about:blank';
            document.body.style.overflow = '';
            if (yangila) { window.location.reload(); }
        }
        document.querySelectorAll('.tz-qopen').forEach(function(b) {
            b.addEventListener('click', function() { och(b.getAttribute('data-url')); });
        });
        document.getElementById('tz-qclose').addEventListener('click', function() { yop(true); });
        modal.addEventListener('click', function(e) { if (e.target === modal) { yop(true); } });
        window.addEventListener('message', function(e) {
            if (e.data === 'tz-q-saved') { yop(true); }
        });
    });
");

maket::tugat();
