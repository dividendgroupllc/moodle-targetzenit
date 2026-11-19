<?php
// O'QUVCHI 360 — bitta o'quvchining to'liq kartasi (maket «O'quvchi 360»,
// PowerSchool Quick Lookup naqshi): barcha fanlar (FB/BSB/CHSB/jami/baho),
// davomat, UV intizomi, imtihonlar, sharhlar. Kokpit/jurnalda o'quvchi
// ismini bosganda ochiladi.
//
// RUXSAT: shu o'quvchiga DARS BERADIGAN o'qituvchi yoki rahbariyat.

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use local_targetzenit\exam;
use local_targetzenit\output\maket;
use local_targetzenit\sharh;
use local_targetzenit\student_view;
use local_targetzenit\timetable;

$userid = optional_param('userid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$termidx = optional_param('term', -1, PARAM_INT);

require_login();
$context = context_system::instance();
$year = timetable::current_year();

// GURUH + O'QUVCHI FILTRI (2026-11-19): o'qituvchi o'z guruhlaridan birini,
// undagi o'quvchini tanlab 360 kartasini ko'radi.
$mening = \local_targetzenit\journal::my_courses((int) $USER->id, $year);

$grstudents = [];
if ($courseid && isset($mening[$courseid])) {
    $cctx = context_course::instance($courseid);
    foreach (\local_targetzenit\lesson::students($cctx) as $st) {
        $grstudents[(int) $st->id] = fullname($st);
    }
    if (!$userid || !isset($grstudents[$userid])) {
        $userid = $grstudents ? (int) array_key_first($grstudents) : 0;
    }
}
if (!$userid) {
    if ($mening) {
        redirect(new moodle_url('/local/targetzenit/oquvchi360.php',
            ['courseid' => array_key_first($mening)]));
    }
    throw new moodle_exception('nopermissions', 'error', '',
        get_string('s360_title', 'local_targetzenit'));
}

$ruxsat = has_capability('local/targetzenit:viewteachers', $context);
if (!$ruxsat) {
    foreach (enrol_get_users_courses($userid, true, 'id') as $c) {
        if (has_capability('local/targetzenit:teach', context_course::instance($c->id))) {
            $ruxsat = true;
            break;
        }
    }
}
if (!$ruxsat) {
    throw new moodle_exception('nopermissions', 'error', '',
        get_string('s360_title', 'local_targetzenit'));
}

$oquvchi = core_user::get_user($userid, '*', MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/oquvchi360.php', ['userid' => $userid]));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(fullname($oquvchi));
$PAGE->requires->css('/local/targetzenit/styles.css');

$terms = array_values(timetable::terms($year));
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
$term = $terms[$termidx] ?? null;

// --- Ma'lumot
$fanlar = $term ? student_view::grades((int) $userid, $term->startdate, $term->enddate) : [];
$imtihonlar = $term ? exam::for_student((int) $userid, $year, $term->name) : [];
$sharhlar = sharh::mine((int) $userid, 20);

// Umumiy davomat + UV intizomi (shu chorak)
$dav = ['absent' => 0, 'excused' => 0, 'late' => 0, 'jami' => 0];
foreach ($fanlar as $v) {
    foreach ($v['davomat'] as $k => $n) {
        if (isset($dav[$k])) {
            $dav[$k] += $n;
        }
    }
}
$kelgan = max(0, (int) $dav['jami'] - (int) $dav['absent'] - (int) $dav['excused']);
$davfoiz = $dav['jami'] ? round(($kelgan + $dav['late']) / $dav['jami'] * 100) : 0;

// UV intizomi: shu chorak, barcha fanlar — hwstatus agregat
$uvjami = 0; $uvbajardi = 0;
if ($term) {
    $r = $DB->get_record_sql("
        SELECT COUNT(CASE WHEN r.hwstatus IS NOT NULL THEN 1 END) AS jami,
               COUNT(CASE WHEN r.hwstatus = 'bajardi' THEN 1 END) AS bajardi
          FROM {local_targetzenit_record} r
          JOIN {local_targetzenit_lesson} l ON l.id = r.lessonid
         WHERE r.userid = :u AND l.lessondate >= :a AND l.lessondate <= :b",
        ['u' => $userid, 'a' => $term->startdate, 'b' => $term->enddate]);
    $uvjami = (int) ($r->jami ?? 0);
    $uvbajardi = (int) ($r->bajardi ?? 0);
}
$uvfoiz = $uvjami ? round($uvbajardi / $uvjami * 100) : null;

// Pill rangi baho bo'yicha
$pill = static function ($baho): string {
    $b = (int) $baho;
    $r = $b >= 4 ? 'g' : ($b === 3 ? 'a' : 'r');
    return 'tz-pill tz-pill-' . $r;
};

// Imtihon holati va sharh kategoriyasi — DB'da o'zbekcha kalit, LEKIN
// o'qituvchining tilida ko'rsatiladi (bh_holat_*, sh_kat_* satrlari).
$holatmatn = static function (string $kalit): string {
    $kalit = trim($kalit);
    if ($kalit === '') {
        return '—';
    }
    $sm = get_string_manager();
    return $sm->string_exists('bh_holat_' . $kalit, 'local_targetzenit')
        ? get_string('bh_holat_' . $kalit, 'local_targetzenit')
        : s($kalit);
};
$katmatn = static function (string $kalit): string {
    $kalit = trim($kalit);
    $sm = get_string_manager();
    return $sm->string_exists('sh_kat_' . $kalit, 'local_targetzenit')
        ? get_string('sh_kat_' . $kalit, 'local_targetzenit')
        : s($kalit);
};

maket::boshla('s360');
echo html_writer::start_div('tz-ishstoli-wrap');

try {

// ----------------------------------------------------------------- SARLAVHA
echo html_writer::start_div('tz-s360-head');
$bh = \core_text::strtoupper(
    \core_text::substr((string) $oquvchi->firstname, 0, 1)
    . \core_text::substr((string) $oquvchi->lastname, 0, 1));
echo html_writer::tag('span', s($bh), ['class' => 'tz-s360-foto tz-s360-ava']);
echo html_writer::div(
    html_writer::tag('div', fullname($oquvchi), ['class' => 'tzp-h1'])
    . html_writer::tag('div', s((string) $oquvchi->idnumber),
        ['class' => 'tzp-sub mb-0']),
    'tz-s360-inf');
echo html_writer::tag('span', '', ['style' => 'flex:1']);

// --- GURUH FILTRI (o'qituvchining kurslari)
if ($mening) {
    echo html_writer::start_tag('form', ['method' => 'get',
        'action' => new moodle_url('/local/targetzenit/oquvchi360.php'),
        'class' => 'd-inline m-0 me-2']);
    echo html_writer::start_tag('select', ['name' => 'courseid',
        'class' => 'tz-kurs-sel', 'onchange' => 'this.form.submit()',
        'title' => get_string('group', 'local_targetzenit')]);
    echo html_writer::tag('option', '— ' . get_string('group', 'local_targetzenit'),
        ['value' => '0']);
    foreach ($mening as $cid => $nom) {
        echo html_writer::tag('option', format_string((string) $nom),
            ['value' => $cid] + ((int) $cid === (int) $courseid ? ['selected' => 'selected'] : []));
    }
    echo html_writer::end_tag('select');
    echo html_writer::end_tag('form');
}

// --- O'QUVCHI FILTRI (tanlangan guruhdagi o'quvchilar)
if ($grstudents) {
    echo html_writer::start_tag('form', ['method' => 'get',
        'action' => new moodle_url('/local/targetzenit/oquvchi360.php'),
        'class' => 'd-inline m-0 me-2']);
    echo html_writer::empty_tag('input',
        ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
    echo html_writer::start_tag('select', ['name' => 'userid',
        'class' => 'tz-kurs-sel', 'onchange' => 'this.form.submit()',
        'title' => get_string('student', 'local_targetzenit')]);
    foreach ($grstudents as $sid => $nom) {
        echo html_writer::tag('option', s((string) $nom),
            ['value' => $sid] + ((int) $sid === (int) $userid ? ['selected' => 'selected'] : []));
    }
    echo html_writer::end_tag('select');
    echo html_writer::end_tag('form');
}

// --- CHORAK FILTRI
if (count($terms) > 1) {
    echo html_writer::start_tag('form', ['method' => 'get',
        'action' => new moodle_url('/local/targetzenit/oquvchi360.php'),
        'class' => 'd-inline m-0']);
    echo html_writer::empty_tag('input',
        ['type' => 'hidden', 'name' => 'userid', 'value' => $userid]);
    echo html_writer::empty_tag('input',
        ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
    echo html_writer::start_tag('select', ['name' => 'term',
        'class' => 'tz-kurs-sel', 'onchange' => 'this.form.submit()']);
    foreach ($terms as $i => $t) {
        echo html_writer::tag('option', format_string((string) $t->name),
            ['value' => $i] + ($i === $termidx ? ['selected' => 'selected'] : []));
    }
    echo html_writer::end_tag('select');
    echo html_writer::end_tag('form');
}
echo html_writer::end_div();

echo html_writer::start_div('tz-s360-grid');

// ============================================= BARCHA FANLAR
echo html_writer::start_div('tzp-card');
echo html_writer::tag('h5', get_string('s360_subjects', 'local_targetzenit')
    . ($term ? ' · ' . format_string((string) $term->name) : ''));
if (!$fanlar) {
    echo html_writer::div(get_string('s360_nodata', 'local_targetzenit'), 'text-muted');
} else {
    echo html_writer::start_div('', ['style' => 'overflow-x:auto']);
    echo html_writer::start_tag('table', ['class' => 'tz-mini']);
    echo html_writer::tag('thead', html_writer::tag('tr',
        html_writer::tag('th', get_string('subject', 'local_targetzenit'))
        . html_writer::tag('th', get_string('s360_fb', 'local_targetzenit'))
        . html_writer::tag('th', 'BSB')
        . html_writer::tag('th', 'CHSB')
        . html_writer::tag('th', get_string('s360_total', 'local_targetzenit'))
        . html_writer::tag('th', get_string('grade', 'local_targetzenit'))));
    echo html_writer::start_tag('tbody');
    $fmt = static fn($x) => (!is_scalar($x) || $x === null) ? '—'
        : (($x == (int) $x) ? (int) $x : round((float) $x, 1));
    $kuchli = [];
    $zaif = [];
    foreach ($fanlar as $cid => $v) {
        $ts = exam::term_score((int) $userid, (int) $cid, $year, $term->name);
        $ort = (isset($v['ortacha']) && is_scalar($v['ortacha'])) ? $v['ortacha'] : '—';
        $bahoval = (isset($ts->baho) && is_scalar($ts->baho)) ? (int) $ts->baho : 0;
        $totalval = (isset($ts->total) && is_scalar($ts->total)) ? (int) $ts->total : null;
        $fannomi = format_string((string) ($v['course']->fullname ?? ''));
        // Avto-izoh uchun: kuchli (o'rtacha>=8) va e'tibor talab (<6) fanlar
        if (isset($v['ortacha']) && is_scalar($v['ortacha'])) {
            if ((float) $v['ortacha'] >= 8) {
                $kuchli[] = $fannomi;
            } else if ((float) $v['ortacha'] < 6) {
                $zaif[] = $fannomi;
            }
        }
        echo html_writer::tag('tr',
            html_writer::tag('td', $fannomi)
            . html_writer::tag('td', (string) $ort)
            . html_writer::tag('td', (string) $fmt($ts->bsb ?? null))
            . html_writer::tag('td', (string) $fmt($ts->chsb ?? null))
            . html_writer::tag('td', $totalval !== null ? (string) $totalval : '—')
            . html_writer::tag('td', $bahoval
                ? html_writer::tag('span', (string) $bahoval, ['class' => $pill($bahoval)])
                : '—'));
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();

    // Avto-izoh (maket: «Kuchli: ... · E'tibor talab: ...»)
    if ($kuchli || $zaif) {
        $qism = [];
        if ($kuchli) {
            $qism[] = html_writer::tag('b', get_string('s360_strong', 'local_targetzenit'))
                . ' ' . implode(', ', $kuchli);
        }
        if ($zaif) {
            $qism[] = html_writer::tag('b', get_string('s360_weak', 'local_targetzenit'))
                . ' ' . implode(', ', $zaif);
        }
        echo html_writer::div(implode(' · ', $qism), 'tz-s360-izoh');
    }
}
echo html_writer::end_div();

// ============================================= DAVOMAT
$kv = static function (string $nom, string $qiymat, string $pillcls = '', string $style = ''): string {
    $b = $pillcls
        ? html_writer::tag('span', $qiymat, ['class' => $pillcls])
        : html_writer::tag('b', $qiymat, $style ? ['style' => $style] : []);
    return html_writer::div(html_writer::tag('span', $nom) . $b, 'tzp-kv');
};
// Davomat + UV intizomi — bitta ustunda (maket: o'ng tomonda ikkisi tik).
echo html_writer::start_div('');   // ustun o'ramchi
try {
echo html_writer::start_div('tzp-card');
echo html_writer::tag('h5', get_string('s360_attendance', 'local_targetzenit')
    . ' · ' . userdate(time(), '%B'));
echo $kv(get_string('s360_att_inlessons', 'local_targetzenit'),
    ($kelgan + (int) $dav['late']) . ' / ' . (int) $dav['jami']
    . ' (' . $davfoiz . '%)');
echo $kv(get_string('s360_att_absent', 'local_targetzenit'),
    (int) $dav['absent'] . ' ' . get_string('s360_lessons', 'local_targetzenit'),
    $dav['absent'] ? 'tz-pill tz-pill-r' : '');
echo $kv(get_string('s360_att_excused', 'local_targetzenit'), (string) (int) $dav['excused']);
echo $kv(get_string('s360_att_late', 'local_targetzenit'),
    (int) $dav['late'] . ' ' . get_string('s360_times', 'local_targetzenit'));
echo html_writer::end_div();

// ============================================= UV INTIZOMI
echo html_writer::start_div('tzp-card');
echo html_writer::tag('h5', get_string('s360_hw', 'local_targetzenit'));
if ($uvjami > 0) {
    echo $kv(get_string('s360_hw_done', 'local_targetzenit'), $uvfoiz . '%');
    echo $kv(get_string('s360_hw_count', 'local_targetzenit'),
        $uvbajardi . ' / ' . $uvjami);
} else {
    echo html_writer::div(get_string('s360_hw_none', 'local_targetzenit'), 'text-muted');
}
echo html_writer::end_div();   // UV karti
} catch (\Throwable $e) {
    $ln = $e->getLine();
    foreach ($e->getTrace() as $tr) { if (!empty($tr['file']) && strpos($tr['file'], 'oquvchi360') !== false) { $ln = $tr['line']; break; } }
    echo html_writer::div('⚠ ' . s($e->getMessage()) . ' @ ' . $ln, 'text-danger small');
}
echo html_writer::end_div();   // davomat+UV ustuni

// ============================================= IMTIHONLAR
try {
echo html_writer::start_div('tzp-card');
echo html_writer::tag('h5', get_string('s360_exams', 'local_targetzenit'));
$bor = false;
foreach ($imtihonlar as $im) {
    if (((string) ($im->sitting_status ?? '')) !== 'bekor') {
        $bor = true;
        break;
    }
}
if (!$bor) {
    echo html_writer::div(get_string('s360_noexams', 'local_targetzenit'), 'text-muted');
} else {
    echo html_writer::start_div('', ['style' => 'overflow-x:auto']);
    echo html_writer::start_tag('table', ['class' => 'tz-mini']);
    echo html_writer::tag('thead', html_writer::tag('tr',
        html_writer::tag('th', get_string('s360_ex_name', 'local_targetzenit'))
        . html_writer::tag('th', get_string('subject', 'local_targetzenit'))
        . html_writer::tag('th', get_string('jr_date', 'local_targetzenit'))
        . html_writer::tag('th', get_string('s360_ex_score', 'local_targetzenit'))
        . html_writer::tag('th', get_string('s360_ex_status', 'local_targetzenit'))));
    echo html_writer::start_tag('tbody');
    foreach ($imtihonlar as $im) {
        if (((string) ($im->sitting_status ?? '')) === 'bekor') {
            continue;
        }
        $score = is_scalar($im->score ?? null) ? (float) $im->score : null;
        $maxs = (int) (is_scalar($im->maxscore ?? null) ? $im->maxscore : 0);
        $ball = ($score !== null)
            ? (($score == (int) $score) ? (int) $score : round($score, 1)) . ' / ' . $maxs
            : html_writer::tag('span', '—', ['class' => 'text-muted']);
        $sana = is_scalar($im->sana ?? null) ? (string) $im->sana : '';
        echo html_writer::tag('tr',
            html_writer::tag('td', s((string) $im->turi) . ' ' . format_string((string) $im->name))
            . html_writer::tag('td', format_string((string) ($im->coursename ?? '')))
            . html_writer::tag('td', $sana !== '' ? userdate(strtotime($sana), '%d.%m') : '—')
            . html_writer::tag('td', $ball)
            . html_writer::tag('td', $holatmatn((string) ($im->holat ?? ''))));
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}
echo html_writer::end_div();
} catch (\Throwable $e) {
    $ln = $e->getLine();
    foreach ($e->getTrace() as $tr) { if (!empty($tr['file']) && strpos($tr['file'], 'oquvchi360') !== false) { $ln = $tr['line']; break; } }
    echo html_writer::div('⚠ ' . s($e->getMessage()) . ' @ ' . $ln, 'text-danger small');
}

// ============================================= SHARHLAR (ochiq)
try {
echo html_writer::start_div('tzp-card');
echo html_writer::tag('h5', get_string('s360_reviews', 'local_targetzenit'));
$shbor = false;
foreach ($sharhlar as $sh) {
    if (trim((string) ($sh->content ?? '')) !== '') {
        $shbor = true;
        break;
    }
}
if (!$shbor) {
    echo html_writer::div(get_string('s360_noreviews', 'local_targetzenit'), 'text-muted');
} else {
    foreach ($sharhlar as $sh) {
        $matn = trim((string) ($sh->content ?? ''));
        if ($matn === '') {
            continue;
        }
        $muallif = trim((string) ($sh->afirstname ?? '') . ' ' . (string) ($sh->alastname ?? ''));
        echo html_writer::div(
            ($sh->kategoriya
                ? html_writer::tag('b', $katmatn((string) $sh->kategoriya) . ': ') : '')
            . s((string) $matn)
            . html_writer::tag('small',
                ($muallif !== '' ? ' — ' . s((string) $muallif) : '')
                . ' · ' . userdate((int) $sh->timecreated, '%d.%m.%Y')
                . ((int) $sh->seenat > 0
                    ? ' · ' . get_string('s360_seen', 'local_targetzenit') : ''),
                ['class' => 'text-muted']),
            'tz-s360-sh');
    }
}
echo html_writer::end_div();
} catch (\Throwable $e) {
    $ln = $e->getLine();
    foreach ($e->getTrace() as $tr) { if (!empty($tr['file']) && strpos($tr['file'], 'oquvchi360') !== false) { $ln = $tr['line']; break; } }
    echo html_writer::div('⚠ ' . s($e->getMessage()) . ' @ ' . $ln, 'text-danger small');
}

echo html_writer::end_div();   // tz-s360-grid

} catch (\Throwable $e) {
    // VAQTINCHA TASHXIS: oquvchi360.php dagi ANIQ qatorni trace'dan topadi.
    $joy = basename($e->getFile()) . ':' . (int) $e->getLine();
    foreach ($e->getTrace() as $tr) {
        if (!empty($tr['file']) && strpos($tr['file'], 'oquvchi360') !== false) {
            $joy = basename($tr['file']) . ':' . ($tr['line'] ?? '?');
            break;
        }
    }
    echo html_writer::div('⚠ ' . s($e->getMessage()) . ' @ ' . s($joy),
        'alert alert-warning');
}

echo html_writer::end_div();   // tz-ishstoli-wrap
maket::tugat();
