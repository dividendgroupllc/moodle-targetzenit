<?php
// DARS YOZUVLARI — direktor uchun lenta.
//
// Tuzilma Frappe'dagi «Sotuv nazorati» sahifasidan olingan: master qator
// (kim, qayerda, nima, qachon, qancha, AI bahosi, qisqa xulosa) va qatorni
// bosganda ochiladigan karta — chapda ovoz, o'ngda qisqa tahlil, pastda
// dars jarayoniga o'tish tugmasi.
//
// ODDIY sahifa, admin sahifasi EMAS: `admin_externalpage_setup()` sahifani
// ADMIN DARAXTIDAN qidiradi, daraxtga qo'shish esa `settings.php` da
// `if ($hassiteconfig)` ichida — direktorda `moodle/site:config` yo'q va
// sahifa o'z ruxsati bo'lsa ham "Access denied" berardi.

require(__DIR__ . '/../../config.php');

use local_targetzenit\audio;
use local_targetzenit\dashboard;
use local_targetzenit\output\fidbek;
use local_targetzenit\output\lenta;
use local_targetzenit\output\shell;
use local_targetzenit\timetable;

require_login();

$context = context_system::instance();
require_capability('local/targetzenit:viewanalysis', $context);

$oqituvchi = optional_param('oqituvchi', 0, PARAM_INT);
$guruh = optional_param('guruh', '', PARAM_TEXT);
$from = optional_param('from', '', PARAM_TEXT);
$to = optional_param('to', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$ochir = optional_param('ochir', 0, PARAM_INT);
$turi = optional_param('turi', 'audio', PARAM_ALPHA);   // audio | hammasi

// O'CHIRISH — FAQAT SAYT MA'MURIGA.
//
// Direktor kuzatuvchi: u dars yozuvini, transkriptni yoki tahlilni
// o'chira olmaydi. Sabab oddiy — bu dalil: o'qituvchi haqidagi xulosaning
// asosi. Kuzatuvchi o'zi baho qo'yadigan dalilni yo'q qila olmasligi kerak.
// `viewanalysis` ko'rish uchun yetarli, o'chirish uchun emas.
$ochirishmumkin = has_capability('moodle/site:config', $context);

$tekshir = fn($v, $zaxira) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? $v : $zaxira;
$from = $tekshir($from, date('Y-m-d', strtotime('-30 days')));
$to = $tekshir($to, date('Y-m-d'));

const TZ_YOZUV_PER_PAGE = 20;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/yozuvlar.php'));
shell::setup($PAGE, get_string('recordings', 'local_targetzenit'));

// Havola quruvchi — fidbek va o'chirish redirectlaridan OLDIN kerak.
$url = fn(array $p = []) => new moodle_url('/local/targetzenit/yozuvlar.php', $p + [
    'oqituvchi' => $oqituvchi, 'guruh' => $guruh, 'from' => $from, 'to' => $to]);

// ------------------------------------------------------------------- FIDBEK
// Chiqarishdan OLDIN: saqlagach redirect qilamiz, shunda sahifa yangilansa
// forma qayta yuborilmaydi (POST/Redirect/GET).
if (fidbek::handle()) {
    redirect($url(['page' => $page]), get_string('fb_saved', 'local_targetzenit'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// ------------------------------------------------------------------ O'CHIRISH
if ($ochir && confirm_sesskey()) {
    // Tugmani yashirish yetarli emas — havolani qo'lda yozish mumkin,
    // shuning uchun ruxsat SHU YERDA ham tekshiriladi (ajax.php dagi
    // `reanalyse` bilan bir xil tamoyil).
    require_capability('moodle/site:config', $context);

    $a = $DB->get_record('local_targetzenit_audio', ['id' => $ochir], '*', MUST_EXIST);
    $dars = $DB->get_record('local_targetzenit_lesson', ['id' => $a->lessonid], 'id, courseid');

    if ($dars) {
        $ctx = context_course::instance($dars->courseid, IGNORE_MISSING);
        if ($ctx) {
            get_file_storage()->delete_area_files($ctx->id, audio::COMPONENT,
                audio::FILEAREA, $a->lessonid);
        }
    }
    if ($turi === 'hammasi') {
        // Audio ham, transkript ham, tahlil ham — butunlay
        $DB->delete_records('local_targetzenit_audio', ['id' => $a->id]);
        $xabar = get_string('rec_deleted_all', 'local_targetzenit');
    } else {
        // Faqat audio — transkript va tahlil qoladi
        $a->status = 'deleted';
        $a->filename = null;
        $a->filesize = 0;
        $a->timemodified = time();
        $DB->update_record('local_targetzenit_audio', $a);
        $xabar = get_string('rec_deleted_audio', 'local_targetzenit');
    }
    redirect(new moodle_url('/local/targetzenit/yozuvlar.php'), $xabar, null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$year = timetable::current_year();
$filtrlar = $year ? dashboard::recording_filters($year)
                  : ['oqituvchilar' => [], 'guruhlar' => []];

$natija = dashboard::recordings([
    'teacher' => $oqituvchi, 'groupkey' => $guruh, 'from' => $from, 'to' => $to,
], $page, TZ_YOZUV_PER_PAGE);

echo $OUTPUT->header();
shell::start('yozuvlar', get_string('recordings', 'local_targetzenit'),
    get_string('rec_sub', 'local_targetzenit', $natija['jami']));

// ====================================================== FILTRLAR
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'tz-dir-filters',
    'action' => (new moodle_url('/local/targetzenit/yozuvlar.php'))->out(false)]);

echo html_writer::div(
    html_writer::tag('label', get_string('dir_colteacher', 'local_targetzenit'))
    . html_writer::select([0 => get_string('rec_allteachers', 'local_targetzenit')]
        + $filtrlar['oqituvchilar'], 'oqituvchi', $oqituvchi, false), 'tz-dir-f');

echo html_writer::div(
    html_writer::tag('label', get_string('dir_colgroup', 'local_targetzenit'))
    . html_writer::select(['' => get_string('rec_allgroups', 'local_targetzenit')]
        + $filtrlar['guruhlar'], 'guruh', $guruh, false), 'tz-dir-f');

echo html_writer::div(
    html_writer::tag('label', get_string('rec_from', 'local_targetzenit'))
    . html_writer::empty_tag('input', ['type' => 'date', 'name' => 'from', 'value' => $from]),
    'tz-dir-f');

echo html_writer::div(
    html_writer::tag('label', get_string('rec_to', 'local_targetzenit'))
    . html_writer::empty_tag('input', ['type' => 'date', 'name' => 'to', 'value' => $to]),
    'tz-dir-f');

echo html_writer::div(
    html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary btn-sm',
        'value' => get_string('rec_apply', 'local_targetzenit')])
    . ' ' . html_writer::link(new moodle_url('/local/targetzenit/yozuvlar.php'),
        get_string('rec_reset', 'local_targetzenit'), ['class' => 'btn btn-sm btn-link']),
    'tz-dir-f');

echo html_writer::end_tag('form');

// ============================================================ LENTA
// Chizuvchining O'ZI `classes/output/lenta.php` da — o'qituvchi paneli
// (`views/oq_yozuvlar.php`) xuddi shu lentani o'z qamrovi bilan ko'rsatadi.
// Bu yerda qamrov butun maktab, u yerda esa bitta odam; ko'rinish bitta.
echo lenta::render($natija['rows'], [
    'url' => $url,
    'page' => $page,
    'ism' => true,
    'ochirish' => $ochirishmumkin,
    'fidbek' => true,
]);

if ($natija['jami'] > TZ_YOZUV_PER_PAGE) {
    echo $OUTPUT->paging_bar($natija['jami'], $page, TZ_YOZUV_PER_PAGE, $url());
}

lenta::js($PAGE);

shell::end();

echo $OUTPUT->footer();
