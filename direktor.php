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
use local_targetzenit\reyting;
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
// `embedded` LAYOUT — Moodle sarlavhasi, navigatsiyasi va drawer'larisiz
// toza holst: `<div id="page"><section class="embedded-main">` dan boshqa
// hech narsa yo'q.
//
// NEGA: `standard` da maket Boost konteynerlari ichida qisilib qolardi —
// `#region-main` ba'zi holatlarda `display:inline-flex` bo'ladi, ya'ni quti
// mazmuniga qarab torayadi va `width:100%` ham yordam bermaydi. Manfiy
// margin bilan ham yechilmadi (0.22.1).
//
// Maketning o'z yon paneli bor, shuning uchun Moodle navigatsiyasi baribir
// takrorlanardi. Qaytish yo'li yon panel tepasidagi havolada.
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('dir_panel', 'local_targetzenit'));
$PAGE->set_heading('');

$year = timetable::current_year();

// ---------------------------------------------------------------- YORDAMCHI

/** Holat chipi: rang + MATN (rang yolg'iz ma'no tashlamaydi). */
function tz_chip(string $status, string $matn, string $title = ''): string {
    return html_writer::tag('span', s($matn),
        ['class' => 'tz-dir-chip tz-dir-' . $status, 'title' => $title]);
}

/** Ism bosh harflari — avatar uchun. */
function tz_av(string $ism): string {
    $q = preg_split('/\s+/', trim($ism)) ?: [];
    $b = '';
    foreach (array_slice($q, 0, 2) as $w) {
        $b .= \core_text::substr($w, 0, 1);
    }
    return html_writer::tag('span', s($b ?: '—'), ['class' => 'tz-dir-av']);
}

/**
 * Ball halqasi. Rang NORMAGA NISBATAN — mutlaq shkalada emas.
 */
function tz_donut(?int $ball): string {
    if ($ball === null) {
        return html_writer::tag('span', '—', ['class' => 'tz-dir-sub']);
    }
    $band = dashboard::band($ball);
    $rang = ['good' => 'var(--tz-good)', 'warning' => 'var(--tz-warning)',
             'serious' => 'var(--tz-serious)', 'critical' => 'var(--tz-critical)'][$band['status']]
        ?? 'var(--tz-brand)';
    $c = 94.2;                       // 2*pi*15
    $t = round($c * min(100, max(0, $ball)) / 100, 1);

    $svg = '<svg width="38" height="38" viewBox="0 0 38 38" aria-hidden="true">'
        . '<circle cx="19" cy="19" r="15" fill="none" stroke="var(--tz-line)" stroke-width="4"/>'
        . '<circle cx="19" cy="19" r="15" fill="none" stroke="' . $rang . '" stroke-width="4"'
        . ' stroke-linecap="round" stroke-dasharray="' . $t . ' ' . ($c - $t) . '"'
        . ' transform="rotate(-90 19 19)"/>'
        . '<text x="19" y="22.5" text-anchor="middle" font-size="10" font-weight="700"'
        . ' fill="currentColor">' . (int) $ball . '</text></svg>';

    return html_writer::div($svg . html_writer::div(
        html_writer::tag('b', $ball . '%')
        . html_writer::div(get_string('dir_overall', 'local_targetzenit'), 'tz-dir-sub')),
        'tz-dir-donut');
}

/** Normaga nisbatan chiziq: to'ldirish + norma belgisi. */
function tz_bar(?float $ball, ?int $norma = null): string {
    if ($ball === null) {
        return '';
    }
    $ichi = html_writer::tag('i', '', ['style' => 'width:' . min(100, max(0, $ball)) . '%']);
    if ($norma !== null) {
        $ichi .= html_writer::tag('u', '', [
            'style' => 'left:' . $norma . '%',
            'title' => get_string('dir_normis', 'local_targetzenit', $norma),
        ]);
    }
    return html_writer::div($ichi, 'tz-dir-bar');
}

/** Sparkline — bitta chiziq, raqamsiz. Tashqi kutubxona yo'q (CSP). */
function tz_spark(array $qiymat): string {
    $qiymat = array_values(array_filter($qiymat, fn($v) => $v !== null));
    if (count($qiymat) < 2) {
        return '';
    }
    $w = 96;
    $h = 24;
    $n = count($qiymat);
    $yol = [];
    foreach ($qiymat as $i => $v) {
        $x = round($i * ($w - 2) / ($n - 1) + 1, 1);
        $y = round($h - 2 - (min(100, max(0, $v)) / 100) * ($h - 4), 1);
        $yol[] = ($i ? 'L' : 'M') . $x . ' ' . $y;
    }
    return '<svg class="tz-dir-spark" width="' . $w . '" height="' . $h . '"'
        . ' viewBox="0 0 ' . $w . ' ' . $h . '" aria-hidden="true">'
        . '<path d="' . implode(' ', $yol) . '" fill="none" stroke="currentColor"'
        . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/** Stat plitasi. */
function tz_tile(string $qiymat, string $yorliq, string $izoh = '',
                 string $qoshimcha = '', string $status = ''): string {
    $h = html_writer::div(s($yorliq), 'tz-dir-lab');
    $h .= html_writer::div(s($qiymat), 'tz-dir-num' . ($status ? ' tz-dir-t-' . $status : ''));
    if ($izoh !== '') {
        $h .= html_writer::div(s($izoh), 'tz-dir-sub');
    }
    if ($qoshimcha !== '') {
        $h .= html_writer::div($qoshimcha, 'tz-dir-sparkwrap');
    }
    return html_writer::div($h, 'tz-dir-tile');
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

echo html_writer::start_div('tz-dir');
echo html_writer::start_div('tz-dir-shell');

// ====================================================== YON PANEL
echo html_writer::start_div('tz-dir-side');
echo html_writer::div(html_writer::tag('span', '◧') . 'Edulytics', 'tz-dir-logo');

// `embedded` da Moodle navigatsiyasi yo'q — qaytish yo'lini O'ZIMIZ beramiz,
// aks holda direktor sahifada qamalib qoladi.
echo html_writer::link(new moodle_url('/my/'),
    '← ' . format_string($SITE->shortname ?: $SITE->fullname),
    ['class' => 'tz-dir-back']);

echo html_writer::div(get_string('dir_navmain', 'local_targetzenit'), 'tz-dir-navlab');
foreach ([
    'darslar' => get_string('dir_navlessons', 'local_targetzenit'),
    'reyting' => get_string('dir_navranks', 'local_targetzenit'),
] as $k => $nom) {
    echo html_writer::link(
        new moodle_url('/local/targetzenit/direktor.php', ['view' => $k]),
        $nom, ['class' => 'tz-dir-navitem' . ($view === $k ? ' tz-on' : '')]);
}
echo html_writer::link(new moodle_url('/local/targetzenit/yozuvlar.php'),
    get_string('recordings', 'local_targetzenit'), ['class' => 'tz-dir-navitem']);

echo html_writer::div(get_string('dir_navmanage', 'local_targetzenit'), 'tz-dir-navlab');
echo html_writer::link(new moodle_url('/local/targetzenit/tutorlar.php'),
    get_string('dir_tutors', 'local_targetzenit'), ['class' => 'tz-dir-navitem']);
echo html_writer::link(new moodle_url('/local/targetzenit/index.php'),
    get_string('timetable', 'local_targetzenit'), ['class' => 'tz-dir-navitem']);
if (has_capability('moodle/site:config', $context)) {
    echo html_writer::link(new moodle_url('/admin/settings.php',
        ['section' => 'local_targetzenit']),
        get_string('dir_navsettings', 'local_targetzenit'), ['class' => 'tz-dir-navitem']);
}

// Foydalanuvchi — yon panel pastida (embedded'da Moodle avatar menyusi yo'q)
echo html_writer::div(
    html_writer::div(s(fullname($USER)), 'tz-dir-username')
    . html_writer::link(new moodle_url('/login/logout.php', ['sesskey' => sesskey()]),
        get_string('logout'), ['class' => 'tz-dir-logout']),
    'tz-dir-user');
echo html_writer::end_div();

// ====================================================== ASOSIY USTUN
echo html_writer::start_div('tz-dir-main');

$soat = (int) date('H');
$salom = $soat < 12 ? 'dir_morning' : ($soat < 18 ? 'dir_afternoon' : 'dir_evening');

echo html_writer::start_div('tz-dir-top');
echo html_writer::div(
    html_writer::div(format_string($SITE->fullname), 'tz-dir-school')
    . html_writer::tag('h2',
        get_string($salom, 'local_targetzenit', $USER->firstname), ['class' => 'tz-dir-hello']));

// Qidiruv — maketdagidek YUQORI qatorda, ikkala ekranda ham bir joyda.
echo html_writer::start_div('tz-dir-tools');
echo html_writer::start_tag('form',
    ['method' => 'get', 'action' => (new moodle_url('/local/targetzenit/direktor.php'))->out(false),
     'class' => 'tz-dir-search']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view', 'value' => $view]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'date', 'value' => $date]);
echo html_writer::tag('span', '⌕', ['class' => 'tz-dir-searchicon']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'q', 'value' => $qidiruv,
    'placeholder' => get_string('dir_search', 'local_targetzenit')]);
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

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

echo html_writer::end_div();   // .tz-dir-main
echo html_writer::end_div();   // .tz-dir-shell
echo html_writer::end_div();   // .tz-dir
echo $OUTPUT->footer();
