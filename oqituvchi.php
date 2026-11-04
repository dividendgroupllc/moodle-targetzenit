<?php
// O'QITUVCHI PANELI — «Mening darslarim».
//
// BITTA RENDERER, IKKI AUDITORIYA (DIREKTOR_PANELI.md 5.1, 7-tamoyil):
// o'qituvchi o'zini ko'radi, direktor/zavuch `?userid=N` bilan boshqa odamni.
// Ruxsat faqat KIMNI ko'rishni hal qiladi, NIMANI ko'rishni emas — o'qituvchi
// o'zi haqida hamma narsani ko'radi (ball, trend, kategoriya profili). Yashirin
// baho ishonchni buzadi, shuning uchun ikki sahifa qilinmadi.
//
// Sahifa javob beradigan savol: "mening hamma darslarim bo'yicha nima holat va
// qaysi darsda nima yetishmayapti?" — shuning uchun markazda reyting emas,
// DARS-DARS ro'yxati va har qatorda "shu darsga o'tish" tugmasi turadi.

require(__DIR__ . '/../../config.php');

use local_targetzenit\ai;
use local_targetzenit\dashboard;
use local_targetzenit\output\fidbek;
use local_targetzenit\output\shell;
use local_targetzenit\output\ui;
use local_targetzenit\teacher;
use local_targetzenit\timetable;

require_login();

$userid = optional_param('userid', 0, PARAM_INT) ?: (int) $USER->id;
$view = optional_param('view', 'hisob', PARAM_ALPHA);          // hisob | yozuvlar
$davrkod = optional_param('davr', 'chorak', PARAM_ALPHA);
$filter = optional_param('filter', 'all', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$groupkey = optional_param('gk', '', PARAM_TEXT);
$subjectkey = optional_param('sk', '', PARAM_TEXT);

// Yozuvlar lentasining filtrlari — direktordagi `yozuvlar.php` bilan bir xil
$guruh = optional_param('guruh', '', PARAM_TEXT);
$from = optional_param('from', '', PARAM_TEXT);
$to = optional_param('to', '', PARAM_TEXT);

if (!in_array($view, ['hisob', 'yozuvlar'], true)) {
    $view = 'hisob';
}
if (!array_key_exists($davrkod, teacher::periods())) {
    $davrkod = 'chorak';
}
$sana = fn($v, $zaxira) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? $v : $zaxira;
$from = $sana($from, date('Y-m-d', strtotime('-30 days')));
$to = $sana($to, date('Y-m-d'));

$syscontext = context_system::instance();
$ozim = ($userid === (int) $USER->id);

if (!teacher::korish_mumkinmi($userid)) {
    throw new moodle_exception('nopermissions', 'error', '',
        get_string('oq_noaccess', 'local_targetzenit'));
}

$odam = $DB->get_record('user', ['id' => $userid],
    'id, firstname, lastname, idnumber, email', MUST_EXIST);

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/targetzenit/oqituvchi.php',
    ['view' => $view] + ($ozim ? [] : ['userid' => $userid])));
// To'liq ekran qobig'i — direktor paneli bilan AYNI BIR XIL (`output\shell`).
//
// NEGA `standard` EMAS: Boost konteynerida maket qisilib qolardi va yon
// panelsiz o'qituvchi ko'rinishlar orasida o'tadigan tugmani topa olmasdi.
// Qobiq allaqachon ekranga moslashgan (yon panel `clamp()` bilan torayadi,
// 860px dan pastda gorizontal menyuga aylanadi) — shuning uchun maketni
// qaytadan yozish kerak emas, menyu esa `shell::oq_menu()` dan keladi.
shell::setup($PAGE, $ozim
    ? get_string('oq_mypanel', 'local_targetzenit')
    : get_string('oq_card', 'local_targetzenit'));

$year = timetable::current_year();

/** Joriy sahifaning havolasi — tanlovlar saqlanadi. */
$url = function(array $p = []) use ($ozim, $userid, $view, $davrkod, $filter,
                                    $groupkey, $subjectkey, $guruh, $from, $to) {
    $asos = ['view' => $view];
    if (!$ozim) {
        $asos['userid'] = $userid;
    }
    if ($view === 'yozuvlar') {
        $asos += ['from' => $from, 'to' => $to];
        if ($guruh !== '') {
            $asos['guruh'] = $guruh;
        }
    } else {
        $asos += ['davr' => $davrkod, 'filter' => $filter];
        if ($groupkey !== '') {
            $asos['gk'] = $groupkey;
        }
        if ($subjectkey !== '') {
            $asos['sk'] = $subjectkey;
        }
    }
    return new moodle_url('/local/targetzenit/oqituvchi.php', $p + $asos);
};

// ------------------------------------------------------------------- FIDBEK
// Chiqarishdan OLDIN: saqlagach redirect qilamiz, shunda sahifa yangilansa
// forma qayta yuborilmaydi (POST/Redirect/GET). Formani faqat `givefeedback`
// ruxsati borlar ko'radi — o'qituvchi o'z lentasida fidbekni FAQAT O'QIYDI.
if (fidbek::handle()) {
    redirect($url(['page' => $page]), get_string('fb_saved', 'local_targetzenit'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

if (!$year) {
    echo $OUTPUT->notification(get_string('timetable_empty', 'local_targetzenit'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$davr = teacher::period($davrkod);
$hisob = teacher::summary($userid, $davr);
$sanoq = teacher::counts($userid, $davr);
$norma = ai::norm_overall();

// ====================================================== SARLAVHA (qobiqda)
$ism = dashboard::ism($odam->firstname, $odam->lastname);
$yuklama = teacher::load($userid, $year);

$meta = [];
if ($yuklama && trim((string) $yuklama->subjects) !== '') {
    $meta[] = $yuklama->subjects;
}
if ($yuklama && (int) $yuklama->lessonsweek > 0) {
    $meta[] = get_string('oq_perweek', 'local_targetzenit', (int) $yuklama->lessonsweek);
}
if ($odam->idnumber !== '') {
    $meta[] = $odam->idnumber;
}

// Davr tanlagichi FAQAT hisob ko'rinishida: yozuvlar lentasi sana
// oralig'i bilan ishlaydi (direktordagi lenta bilan bir xil) va ikki xil
// vaqt tanlagichi bir ekranda chalg'itadi.
$vositalar = '';
if ($view === 'hisob') {
    $vositalar = html_writer::start_div('tz-dir-tabs');
    foreach (teacher::periods() as $k => $str) {
        $vositalar .= html_writer::link($url(['davr' => $k, 'page' => 0]),
            get_string($str, 'local_targetzenit'),
            ['class' => 'tz-dir-tab' . ($davrkod === $k ? ' tz-on' : '')]);
    }
    $vositalar .= html_writer::end_div();
}

shell::start_menu($view, ui::av($ism) . s($ism), s(implode(' · ', $meta)),
    $vositalar, shell::oq_menu($userid, $ozim));

echo html_writer::start_div('tz-oq');

// ====================================================== KO'RINISH TANLASH
// Yon panelda ham bor, lekin sahifaning o'zida HAM turadi: 860px dan
// pastda yon panel gorizontal menyuga aylanib yuqoriga chiqadi va unda
// faol band ko'zga unchalik tashlanmaydi.
echo html_writer::start_div('tz-oq-switch');
foreach (['hisob' => 'oq_tab_sum', 'yozuvlar' => 'recordings'] as $k => $str) {
    echo html_writer::link($url(['view' => $k, 'page' => 0]),
        get_string($str, 'local_targetzenit'),
        ['class' => 'tz-oq-sw' . ($view === $k ? ' tz-on' : '')]);
}
echo html_writer::end_div();

if ($view === 'hisob') {
echo html_writer::div(get_string('oq_periodis', 'local_targetzenit', (object) [
    'nom' => $davr['nom'],
    'from' => userdate(strtotime($davr['from']), '%d.%m.%Y'),
    'to' => userdate(strtotime($davr['to']), '%d.%m.%Y'),
]), 'tz-dir-sub mb-2');
}

// Kuzatuv boshlanishi — nega sentabrdagi darslar yo'q ekanini tushuntiradi
if ($view === 'hisob' && $davrkod === 'hammasi') {
    echo html_writer::div(
        get_string('oq_watchfrom', 'local_targetzenit',
            userdate(strtotime(dashboard::kuzatuv_boshi()), '%d.%m.%Y')),
        'tz-dir-sub mb-2');
}

// Direktor boshqa odamni ko'rayotganda qaytish yo'li
if (!$ozim) {
    echo html_writer::div(html_writer::link(
        new moodle_url('/local/targetzenit/direktor.php', ['view' => 'reyting']),
        '← ' . get_string('dir_navranks', 'local_targetzenit'),
        ['class' => 'btn btn-sm btn-outline-secondary']), 'mb-2');
}

// Ko'rinishlar alohida fayllarda — har biri `MOODLE_INTERNAL` bilan
// himoyalangan, ya'ni to'g'ridan-to'g'ri so'rov bilan ochilmaydi.
if ($view === 'yozuvlar') {
    require(__DIR__ . '/views/oq_yozuvlar.php');
} else {
    require(__DIR__ . '/views/oq_hisob.php');
}

echo html_writer::end_div();   // .tz-oq
shell::end();
echo $OUTPUT->footer();
