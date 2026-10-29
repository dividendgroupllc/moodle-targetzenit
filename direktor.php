<?php
// DIREKTOR PANELI — «Bugun» ekrani.
//
// Sahifaning asosiy elementi grafik emas, DIQQAT NAVBATI: direktor
// qidirmaydi, unga ustuvorlik tartibida ko'rsatiladi.
//
// Tamoyillar (DIREKTOR_PANELI.md 5.1):
//   - normaga nisbatan, mutlaq emas
//   - namuna hajmi (n) ko'rinadi
//   - har raqam dalilga olib boradi
//   - reyting emas, ro'yxat: saralash ISM bo'yicha
//   - holat hech qachon faqat rang bilan ifodalanmaydi (raqam + yorliq)

require(__DIR__ . '/../../config.php');

use local_targetzenit\dashboard;
use local_targetzenit\timetable;

require_login();

$context = context_system::instance();
require_capability('local/targetzenit:viewteachers', $context);

$date = optional_param('date', date('Y-m-d'), PARAM_TEXT);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/direktor.php', ['date' => $date]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('dir_panel', 'local_targetzenit'));
$PAGE->set_heading(get_string('dir_panel', 'local_targetzenit'));

$year = timetable::current_year();
$terms = $year ? timetable::terms($year) : [];
$holidays = $year ? timetable::holidays($year) : [];
$kunholat = $terms ? timetable::school_day($date, $terms, $holidays)
    : ['ok' => false, 'reason' => ''];

$boshi = dashboard::kuzatuv_boshi();

/** Holat belgisi: rang + RAQAM/YORLIQ (rang yolg'iz ma'no tashlamaydi). */
function tz_dir_chip(string $status, string $matn, string $title = ''): string {
    return html_writer::tag('span', s($matn), [
        'class' => 'tz-dir-chip tz-dir-' . $status,
        'title' => $title,
    ]);
}

/**
 * Sparkline — bitta chiziq, raqamsiz (stat tile qoidasi).
 * Tashqi kutubxona yo'q: Moodle'da CDN'ga tayanish CSP va oflayn ishlash
 * bo'yicha noto'g'ri, shuning uchun SVG qo'lda chiziladi.
 */
function tz_dir_spark(array $nuqtalar, string $kalit): string {
    $qiymat = [];
    foreach ($nuqtalar as $n) {
        if ($n[$kalit] !== null) {
            $qiymat[] = (int) $n[$kalit];
        }
    }
    if (count($qiymat) < 2) {
        return '';
    }
    $w = 96;
    $h = 24;
    $n = count($qiymat);
    $yol = [];
    foreach ($qiymat as $i => $v) {
        $x = round($i * ($w - 2) / ($n - 1) + 1, 1);
        $y = round($h - 2 - ($v / 100) * ($h - 4), 1);
        $yol[] = ($i ? 'L' : 'M') . $x . ' ' . $y;
    }
    return '<svg class="tz-dir-spark" width="' . $w . '" height="' . $h . '" '
        . 'viewBox="0 0 ' . $w . ' ' . $h . '" aria-hidden="true" focusable="false">'
        . '<path d="' . implode(' ', $yol) . '" fill="none" stroke="currentColor" '
        . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/** Stat plitasi: katta raqam + izoh (+ sparkline). */
function tz_dir_tile(string $qiymat, string $yorliq, string $izoh = '',
                     string $spark = '', string $status = ''): string {
    $ichi = html_writer::div(s($qiymat), 'tz-dir-num' . ($status ? ' tz-dir-t-' . $status : ''));
    $ichi .= html_writer::div(s($yorliq), 'tz-dir-lab');
    if ($izoh !== '') {
        $ichi .= html_writer::div(s($izoh), 'tz-dir-sub');
    }
    if ($spark !== '') {
        $ichi .= html_writer::div($spark, 'tz-dir-sparkwrap');
    }
    return html_writer::div($ichi, 'tz-dir-tile');
}


echo $OUTPUT->header();

if (!$year) {
    echo $OUTPUT->notification(get_string('timetable_empty', 'local_targetzenit'), 'info');
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_div('tz-dir');

// ====================================================== SARLAVHA QATORI
$oldin = date('Y-m-d', strtotime('-1 day', strtotime($date)));
$keyin = date('Y-m-d', strtotime('+1 day', strtotime($date)));
$url = fn($d) => new moodle_url('/local/targetzenit/direktor.php', ['date' => $d]);

$nav = html_writer::link($url($oldin), '‹', ['class' => 'btn btn-sm btn-outline-secondary']);
$nav .= html_writer::tag('span', userdate(strtotime($date), '%d %B, %A'), ['class' => 'tz-dir-date']);
$nav .= html_writer::link($url($keyin), '›', ['class' => 'btn btn-sm btn-outline-secondary']);
if ($date !== date('Y-m-d')) {
    $nav .= html_writer::link($url(date('Y-m-d')), get_string('dir_today', 'local_targetzenit'),
        ['class' => 'btn btn-sm btn-outline-primary ml-2']);
}
$nav .= html_writer::tag('span',
    ($kunholat['ok'] ? s($kunholat['reason']) : '⚠ ' . s($kunholat['reason']))
    . ' · ' . get_string('dir_since', 'local_targetzenit', userdate(strtotime($boshi), '%d.%m.%Y')),
    ['class' => 'tz-dir-meta']);

echo html_writer::div($nav, 'tz-dir-head');

// ============================================== MA'LUMOT SOG'LIGI
foreach (dashboard::health($year) as $h) {
    echo html_writer::div(
        html_writer::tag('b', $h->status === 'serious' ? '⚠ ' : 'ℹ ')
        . s($h->matn) . html_writer::div(s($h->amal), 'tz-dir-sub'),
        'tz-dir-health tz-dir-h-' . $h->status);
}

// ======================================================== HERO
$s = dashboard::today_summary($date);
$trend = dashboard::trend($date);

$jf = dashboard::foiz($s->mavzu, $s->otgan);
$yf = dashboard::foiz($s->yozuv, $s->otgan);

$tiles = tz_dir_tile((string) $s->jami, get_string('dir_lessons_today', 'local_targetzenit'),
    $s->qoldi ? get_string('dir_left', 'local_targetzenit', $s->qoldi) : '');
$tiles .= tz_dir_tile((string) $s->otgan, get_string('dir_passed', 'local_targetzenit'));
$tiles .= tz_dir_tile($jf === null ? '—' : $jf . '%',
    get_string('dir_journal', 'local_targetzenit'),
    $s->otgan ? "{$s->mavzu} / {$s->otgan}" : '',
    tz_dir_spark($trend, 'mavzu'));
$tiles .= tz_dir_tile($yf === null ? '—' : $yf . '%',
    get_string('dir_recorded', 'local_targetzenit'),
    $s->otgan ? "{$s->yozuv} / {$s->otgan}" : '',
    tz_dir_spark($trend, 'yozuv'));

// Normaga nisbatan — diverging: ko'k (+) / qizil (−), nol = xalqaro o'rtacha.
// Namuna hajmi ALBATTA ko'rsatiladi: 2 ta darsdan chiqqan foiz fakt emas.
if ($s->delta === null) {
    $tiles .= tz_dir_tile('—', get_string('dir_vsnorm', 'local_targetzenit'),
        get_string('dir_noanalysis', 'local_targetzenit'));
} else {
    $tiles .= tz_dir_tile(($s->delta >= 0 ? '+' : '') . $s->delta . '%',
        get_string('dir_vsnorm', 'local_targetzenit'),
        'n = ' . $s->delta_n . ($s->delta_n < 5 ? ' ⚠' : ''),
        '', $s->delta >= 0 ? 'pos' : 'neg');
}

echo html_writer::div($tiles, 'tz-dir-hero');

// ============================================== DIQQAT NAVBATI
$a = dashboard::attention($date);
$items = $a['items'] ?? [];

echo html_writer::start_div('tz-dir-card');
echo html_writer::tag('h4',
    '⚠ ' . get_string('dir_attention', 'local_targetzenit')
    . html_writer::tag('span', ' (' . count($items) . ')', ['class' => 'tz-dir-count']),
    ['class' => 'tz-dir-cardtitle']);
echo html_writer::div(
    get_string('dir_attention_window', 'local_targetzenit',
        userdate(strtotime($a['oyna_boshi'] ?? $date), '%d.%m')),
    'tz-dir-sub mb-2');

if (!$items) {
    echo html_writer::div(get_string('dir_attention_empty', 'local_targetzenit'),
        'tz-dir-ok');
} else {
    echo html_writer::start_div('tz-dir-queue');
    foreach ($items as $it) {
        $sana = userdate(strtotime($it->lessondate), '%d.%m');
        $chap = $it->lessonno
            ? trim(($it->starttime ?? '') . ' · ' . $it->lessonno . '-dars')
            : $sana;

        // Faqat-o'qish dars kartasi: direktorda kurs kontekstidagi `teach`
        // ruxsati yo'q, shuning uchun kokpitga havola qilinmaydi.
        $amal = '';
        if ($it->slotid) {
            $amal = html_writer::link(new moodle_url('/local/targetzenit/dars_tahlil.php',
                ['slotid' => $it->slotid]),
                get_string('dir_openlesson', 'local_targetzenit'),
                ['class' => 'btn btn-sm btn-outline-secondary']);
        }

        echo html_writer::div(
            html_writer::div(tz_dir_chip($it->status, $it->kind), 'tz-dir-qchip')
            . html_writer::div(
                html_writer::div(s($chap) . ' · ' . s($it->title), 'tz-dir-qtitle')
                . html_writer::div(s($it->detail), 'tz-dir-sub'), 'tz-dir-qbody')
            . html_writer::div($amal, 'tz-dir-qact'),
            'tz-dir-qrow tz-dir-l' . $it->level);
    }
    echo html_writer::end_div();

    if (!empty($a['jami1']) && $a['jami1'] > dashboard::NAVBAT_QATOR) {
        echo html_writer::div(
            get_string('dir_more', 'local_targetzenit', $a['jami1']), 'tz-dir-sub mt-2');
    }
}
echo html_writer::end_div();

// ================================================= KUN SETKASI
$g = dashboard::day_grid($date);

echo html_writer::start_div('tz-dir-card');
echo html_writer::tag('h4', get_string('dir_grid', 'local_targetzenit'),
    ['class' => 'tz-dir-cardtitle']);

if (!$g['darslar']) {
    echo html_writer::div(get_string('dir_grid_empty', 'local_targetzenit'), 'tz-dir-sub');
} else {
    echo html_writer::div(
        get_string('dir_grid_legend', 'local_targetzenit'), 'tz-dir-sub mb-2');

    echo html_writer::start_div('tz-dir-gridwrap');
    echo html_writer::start_tag('table', ['class' => 'tz-dir-grid']);

    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('dir_teacher', 'local_targetzenit'),
        ['class' => 'tz-dir-gname']);
    foreach ($g['darslar'] as $no => $vaqt) {
        echo html_writer::tag('th',
            $no . html_writer::div(s((string) $vaqt), 'tz-dir-gtime'));
    }
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($g['qatorlar'] as $q) {
        echo html_writer::start_tag('tr');

        $ism = s($q->ism ?: '—');
        if (!$q->bogli) {
            // Bog'lanmagan kalit: bu darslar hech kimning hisobotiga tushmaydi
            $ism .= ' ' . tz_dir_chip('warning', get_string('dir_unlinked', 'local_targetzenit'),
                get_string('dir_unlinked_help', 'local_targetzenit'));
        }
        echo html_writer::tag('td',
            $ism . html_writer::div(s(implode(', ', array_keys($q->fanlar))), 'tz-dir-sub'),
            ['class' => 'tz-dir-gname']);

        foreach ($g['darslar'] as $no => $vaqt) {
            $k = $q->kataklar[$no] ?? null;
            if (!$k) {
                echo html_writer::tag('td', '', ['class' => 'tz-dir-cell tz-dir-c-none']);
                continue;
            }
            if (!$k->otgan) {
                echo html_writer::tag('td',
                    html_writer::tag('span', '·', [
                        'class' => 'tz-dir-box tz-dir-c-future',
                        'title' => s($k->fan . ' · ' . $k->guruh . ' · '
                            . get_string('dir_notyet', 'local_targetzenit')),
                    ]), ['class' => 'tz-dir-cell']);
                continue;
            }

            $holat = [0 => 'critical', 1 => 'serious', 2 => 'warning', 3 => 'good'][$k->bajarildi];
            $title = $k->fan . ' · ' . $k->guruh . "\n"
                . ($k->mavzu ? '✔' : '✖') . ' mavzu'
                . "\n" . ($k->yoqlama ? '✔' : '✖') . ' yo\'qlama'
                . ($k->yoqlama ? " ({$k->yoqlama}/{$k->oquvchi})" : '')
                . "\n" . ($k->yozuv ? '✔' : '✖') . ' yozuv'
                . "\n" . ($k->baho ? "baho: {$k->baho}" : 'baho qo\'yilmagan')
                . ($k->delta !== null ? "\ntahlil: normadan "
                    . ($k->delta >= 0 ? '+' : '') . $k->delta . '%' : '');

            $ichi = html_writer::tag('span', (string) $k->bajarildi, [
                'class' => 'tz-dir-box tz-dir-c-' . $holat,
                'title' => s($title),
            ]);
            if ($k->baho) {
                $ichi .= html_writer::tag('i', '', ['class' => 'tz-dir-dot',
                    'title' => get_string('dir_hasgrade', 'local_targetzenit')]);
            }
            $ichi = html_writer::link(new moodle_url('/local/targetzenit/dars_tahlil.php',
                ['slotid' => $k->slotid]), $ichi, ['class' => 'tz-dir-celllink']);
            echo html_writer::tag('td', $ichi, ['class' => 'tz-dir-cell']);
        }
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::end_div();   // .tz-dir
echo $OUTPUT->footer();
