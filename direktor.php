<?php
// DIREKTOR PANELI — «Darslar tahlili» va «Reytinglar».
//
// Dizayn: Edulytics maketi (yon panel + stat plitalari + jadval).
// React/Vite versiyasidan VOZ KECHILDI — backend allaqachon shu yerda
// ishlayapti, shuning uchun faqat ko'rinish ko'chirildi.
//
// MAKETDAN BITTA ATAYLAB CHEKINISH: maketda reyting mutlaq foizda edi va
// 61% "E'tibor kerak" deb qizil turardi. Xalqaro o'rtacha esa 59%
// (`ai::norm_overall()`) — ya'ni u dars o'rtachadan YUQORI. Shuning uchun
// hamma ball NORMAGA NISBATAN rangalanadi va chiziqda norma belgisi turadi.
// Aks holda panel o'rtacha ishlayotgan o'qituvchini muvaffaqiyatsiz deb
// ko'rsatardi va ishonchni birinchi kuni yo'qotardi.

require(__DIR__ . '/../../config.php');

use local_targetzenit\ai;
use local_targetzenit\dashboard;
use local_targetzenit\output\ui;
use local_targetzenit\reyting;
use local_targetzenit\output\shell;
use local_targetzenit\timetable;

require_login();

$context = context_system::instance();
require_capability('local/targetzenit:viewteachers', $context);

$view = optional_param('view', 'darslar', PARAM_ALPHA);        // darslar | reyting
$date = optional_param('date', date('Y-m-d'), PARAM_TEXT);
$filter = optional_param('filter', 'all', PARAM_ALPHA);        // all|recorded|missing|upcoming
$page = optional_param('page', 0, PARAM_INT);
$qidiruv = optional_param('q', '', PARAM_TEXT);
$davrkod = optional_param('davr', 'oy', PARAM_ALPHA);          // hafta | oy | chorak
$kesim = optional_param('kesim', 'teacher', PARAM_ALPHA);      // teacher | class

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}
const TZ_PER_PAGE = 20;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/direktor.php', ['view' => $view]));
// Qobiq (yon panel + sarlavha) `classes/output/shell.php` da — xuddi shu
// qobiq yozuvlar va tutorlar sahifalarida ham ishlatiladi, shunda
// navigatsiya sahifadan sahifaga o'zgarmaydi.
shell::setup($PAGE, get_string('dir_panel', 'local_targetzenit'));

$year = timetable::current_year();

// ---------------------------------------------------------------- YORDAMCHI
// Elementlarning O'ZI `classes/output/ui.php` da — o'qituvchi paneli ham
// aynan shu chizuvchilarni ishlatadi. Bu yerdagi funksiyalar faqat qisqa nom
// beradi, shunda ko'rinish fayllari (`views/*.php`) o'zgarmaydi.

/** Holat chipi: rang + MATN (rang yolg'iz ma'no tashlamaydi). */
function tz_chip(string $status, string $matn, string $title = ''): string {
    return ui::chip($status, $matn, $title);
}

/** Ism bosh harflari — avatar uchun. */
function tz_av(string $ism): string {
    return ui::av($ism);
}

/** Ball halqasi. Rang NORMAGA NISBATAN — mutlaq shkalada emas. */
function tz_donut(?int $ball): string {
    return ui::donut($ball);
}

/** Normaga nisbatan chiziq: to'ldirish + norma belgisi. */
function tz_bar(?float $ball, ?int $norma = null): string {
    return ui::bar($ball, $norma);
}

/** Sparkline — bitta chiziq, raqamsiz. */
function tz_spark(array $qiymat): string {
    return ui::spark($qiymat);
}

/** Stat plitasi. */
function tz_tile(string $qiymat, string $yorliq, string $izoh = '',
                 string $qoshimcha = '', string $status = ''): string {
    return ui::tile($qiymat, $yorliq, $izoh, $qoshimcha, $status);
}

$url = function(array $p = []) use ($view, $date, $davrkod, $kesim) {
    return new moodle_url('/local/targetzenit/direktor.php',
        $p + ['view' => $view, 'date' => $date, 'davr' => $davrkod, 'kesim' => $kesim]);
};

echo $OUTPUT->header();

if (!$year) {
    echo $OUTPUT->notification(get_string('timetable_empty', 'local_targetzenit'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$soat = (int) date('H');
$salom = $soat < 12 ? 'dir_morning' : ($soat < 18 ? 'dir_afternoon' : 'dir_evening');

// Qidiruv — maketdagidek yuqori qatorda, o'ng tomonda
$qidiruvforma = html_writer::start_tag('form',
    ['method' => 'get', 'action' => (new moodle_url('/local/targetzenit/direktor.php'))->out(false),
     'class' => 'tz-dir-search'])
    . html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view', 'value' => $view])
    . html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'date', 'value' => $date])
    . html_writer::tag('span', '⌕', ['class' => 'tz-dir-searchicon'])
    . html_writer::empty_tag('input', ['type' => 'text', 'name' => 'q', 'value' => $qidiruv,
        'placeholder' => get_string('dir_search', 'local_targetzenit')])
    . html_writer::end_tag('form');

shell::start($view === 'reyting' ? 'reyting' : 'darslar',
    get_string($salom, 'local_targetzenit', $USER->firstname), '', $qidiruvforma);

// Ma'lumot sog'ligi — panel raqamlari noto'g'ri bo'lishi mumkin bo'lgan holatlar
foreach (dashboard::health($year) as $h) {
    echo html_writer::div(
        html_writer::tag('b', $h->status === 'serious' ? '⚠ ' : 'ℹ ')
        . s($h->matn) . html_writer::div(s($h->amal), 'tz-dir-sub'),
        'tz-dir-health tz-dir-h-' . $h->status);
}

// Ko'rinishlar alohida fayllarda — har biri `MOODLE_INTERNAL` bilan
// himoyalangan, ya'ni to'g'ridan-to'g'ri so'rov bilan ochilmaydi.
if ($view === 'reyting') {
    require(__DIR__ . '/views/reyting.php');
} else {
    require(__DIR__ . '/views/darslar.php');
}

shell::end();
echo $OUTPUT->footer();
