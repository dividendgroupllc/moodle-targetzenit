<?php
// «Reytinglar» ko'rinishi — direktor.php ichidan chaqiriladi.
//
// MAKETDAN CHEKINISH: Edulytics maketida reyting mutlaq foizda edi va
// 61% qizil "E'tibor kerak" bo'lib turardi. Xalqaro o'rtacha 59% —
// ya'ni u o'rtachadan yuqori. Shu yerda hamma ball normaga nisbatan
// rangalanadi, chiziqda norma belgisi turadi, va "kuchli/kuchsiz tomon"
// ham HAR KATEGORIYANING O'Z normasiga nisbatan hisoblanadi.
defined('MOODLE_INTERNAL') || die();

$davr = reyting::period($davrkod);
$xulosa = reyting::summary($davr);
$norma = $xulosa['norma'];

// ====================================================== SARLAVHA + DAVR
echo html_writer::start_div('tz-dir-head');
echo html_writer::div(
    html_writer::tag('h1', get_string('dir_rankstitle', 'local_targetzenit'),
        ['class' => 'tz-dir-h1'])
    . html_writer::div(get_string('dir_rankssub', 'local_targetzenit'), 'tz-dir-sub'));

echo html_writer::start_div('tz-dir-tools');
echo html_writer::start_div('tz-dir-tabs');
foreach (['hafta' => 'dir_week', 'oy' => 'dir_month', 'chorak' => 'dir_term'] as $k => $str) {
    echo html_writer::link(
        new moodle_url('/local/targetzenit/direktor.php',
            ['view' => 'reyting', 'davr' => $k, 'kesim' => $kesim]),
        get_string($str, 'local_targetzenit'),
        ['class' => 'tz-dir-tab' . ($davrkod === $k ? ' tz-on' : '')]);
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div(
    get_string('dir_rankperiod', 'local_targetzenit', (object) [
        'nom' => $davr['nom'],
        'from' => userdate(strtotime($davr['from']), '%d.%m'),
        'to' => userdate(strtotime($davr['to']), '%d.%m'),
        'n' => $xulosa['n'],
    ]), 'tz-dir-sub mb-3');

// Tahlil umuman bo'lmasa — bo'sh jadval chizishdan ma'no yo'q
if (!$xulosa['n']) {
    echo html_writer::div(
        html_writer::tag('b', get_string('dir_noranks', 'local_targetzenit'))
        . html_writer::div(get_string('dir_noranks_help', 'local_targetzenit'), 'tz-dir-sub'),
        'tz-dir-card');
    return;
}

// ====================================================== YUQORI UCHLIK
$oqituvchilar = reyting::teachers($davr);
$eng = $oqituvchilar['rows'][0] ?? null;
$sinflar = reyting::groups('class', $davr);
$engsinf = $sinflar[0] ?? null;

echo html_writer::start_div('tz-dir-top3');

// --- Davr eng yaxshi o'qituvchisi
if ($eng) {
    echo html_writer::div(
        html_writer::div(
            html_writer::div(get_string('dir_bestteacher', 'local_targetzenit',
                \core_text::strtolower($davr['nom'])), 'tz-dir-lab')
            . html_writer::div(s($eng->nomi), 'tz-dir-bestname')
            . html_writer::div(s($eng->fan) . ' · '
                . get_string('dir_nlessons', 'local_targetzenit', $eng->darslar), 'tz-dir-lab'))
        . html_writer::div(tz_donut((int) round($eng->ball))),
        'tz-dir-best');
} else {
    echo html_writer::div(
        html_writer::div(get_string('dir_nosample', 'local_targetzenit'), 'tz-dir-lab'),
        'tz-dir-best');
}

// --- Umumiy o'rtacha (normaga nisbatan)
$oz = $xulosa['ozgarish'];
echo html_writer::div(
    html_writer::div(get_string('dir_avgscore', 'local_targetzenit'), 'tz-dir-lab')
    . html_writer::div($xulosa['ball'] . '%', 'tz-dir-num')
    . html_writer::div(
        ($oz === null ? '' : (($oz >= 0 ? '+' : '') . $oz . '% · '))
        . get_string('dir_normis', 'local_targetzenit', $norma), 'tz-dir-sub')
    . html_writer::div(tz_spark(reyting::trend($davr)), 'tz-dir-sparkwrap'),
    'tz-dir-tile');

// --- Eng yuqori natijali sinf
echo html_writer::div(
    html_writer::div(get_string('dir_bestclass', 'local_targetzenit'), 'tz-dir-lab')
    . html_writer::div($engsinf ? s($engsinf->nomi) : '—', 'tz-dir-num')
    . html_writer::div($engsinf
        ? $engsinf->ball . '% · ' . get_string('dir_nlessons', 'local_targetzenit', $engsinf->darslar)
        : get_string('dir_nosample', 'local_targetzenit'), 'tz-dir-sub'),
    'tz-dir-tile');

echo html_writer::end_div();

// ====================================================== REYTING JADVALI
echo html_writer::start_div('tz-dir-card');
echo html_writer::start_div('tz-dir-head');
echo html_writer::start_div('tz-dir-tabs');
foreach (['teacher' => 'dir_byteacher', 'class' => 'dir_byclass',
          'subject' => 'dir_bysubject'] as $k => $str) {
    echo html_writer::link(
        new moodle_url('/local/targetzenit/direktor.php',
            ['view' => 'reyting', 'davr' => $davrkod, 'kesim' => $k]),
        get_string($str, 'local_targetzenit'),
        ['class' => 'tz-dir-tab' . ($kesim === $k ? ' tz-on' : '')]);
}
echo html_writer::end_div();
echo html_writer::tag('span',
    get_string('dir_normlegend', 'local_targetzenit', $norma)
    . ' ' . html_writer::tag('span', '', ['class' => 'tz-dir-normnote']),
    ['class' => 'tz-dir-meta']);
echo html_writer::end_div();

echo html_writer::start_div('tz-dir-tablewrap');
echo html_writer::start_tag('table', ['class' => 'tz-dir-table']);

if ($kesim === 'teacher') {
    echo html_writer::start_tag('thead') . html_writer::start_tag('tr');
    foreach (['', 'dir_colteacher', 'dir_collessons', 'dir_coltrend',
              'dir_colstrong', 'dir_colresult'] as $k) {
        echo html_writer::tag('th', $k ? get_string($k, 'local_targetzenit') : '#');
    }
    echo html_writer::end_tag('tr') . html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($oqituvchilar['rows'] as $i => $r) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td',
            html_writer::tag('span', (string) ($i + 1), ['class' => 'tz-dir-rank']));
        echo html_writer::tag('td', html_writer::div(
            tz_av($r->nomi) . html_writer::div(
                html_writer::div(s($r->nomi), 'tz-dir-chipname')
                . html_writer::div(s($r->fan), 'tz-dir-sub')), 'tz-dir-cellname'));
        echo html_writer::tag('td',
            html_writer::tag('b', (string) $r->darslar)
            . html_writer::div(get_string('dir_lessonword', 'local_targetzenit'), 'tz-dir-sub'));
        echo html_writer::tag('td', $r->trend === null
            ? html_writer::tag('span', '—', ['class' => 'tz-dir-sub'])
            : html_writer::tag('span', ($r->trend >= 0 ? '+' : '') . $r->trend . '%',
                ['class' => 'tz-dir-t-' . ($r->trend >= 0 ? 'pos' : 'neg')]));

        // Kuchli tomon — NORMAGA NISBATAN tanlangan, mutlaq ballga qarab emas
        echo html_writer::tag('td', $r->kuchli
            ? tz_chip('good', $r->kuchli['nom'],
                get_string('dir_strongwhy', 'local_targetzenit', (object) [
                    'avg' => $r->kuchli['avg'],
                    'delta' => ($r->kuchli['delta'] >= 0 ? '+' : '') . $r->kuchli['delta'],
                ]))
            : html_writer::tag('span', '—', ['class' => 'tz-dir-sub']));

        echo html_writer::tag('td',
            html_writer::tag('b', $r->ball . '%') . ' '
            . tz_chip($r->band['status'], $r->band['nom'])
            . tz_bar($r->ball, $norma));
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
} else {
    $rows = reyting::groups($kesim, $davr);
    echo html_writer::start_tag('thead') . html_writer::start_tag('tr');
    foreach (['', $kesim === 'class' ? 'dir_colclass' : 'dir_colsubject',
              'dir_collessons', 'dir_coltrend', 'dir_colresult'] as $k) {
        echo html_writer::tag('th', $k ? get_string($k, 'local_targetzenit') : '#');
    }
    echo html_writer::end_tag('tr') . html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($rows as $i => $r) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td',
            html_writer::tag('span', (string) ($i + 1), ['class' => 'tz-dir-rank']));
        echo html_writer::tag('td', html_writer::tag('b', s($r->nomi)));
        echo html_writer::tag('td',
            html_writer::tag('b', (string) $r->darslar)
            . html_writer::div(get_string('dir_lessonword', 'local_targetzenit'), 'tz-dir-sub'));
        echo html_writer::tag('td', $r->trend === null
            ? html_writer::tag('span', '—', ['class' => 'tz-dir-sub'])
            : html_writer::tag('span', ($r->trend >= 0 ? '+' : '') . $r->trend . '%',
                ['class' => 'tz-dir-t-' . ($r->trend >= 0 ? 'pos' : 'neg')]));
        echo html_writer::tag('td',
            html_writer::tag('b', $r->ball . '%') . ' '
            . tz_chip($r->band['status'], $r->band['nom'])
            . tz_bar($r->ball, $norma));
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
}
echo html_writer::end_tag('table');
echo html_writer::end_div();

// Namunasi yetarli bo'lmaganlar — reytingdan TASHQARIDA, lekin yashirilmaydi
if ($kesim === 'teacher' && !empty($oqituvchilar['kam'])) {
    $ismlar = array_map(fn($r) => $r->nomi . ' (' . $r->darslar . ')', $oqituvchilar['kam']);
    echo html_writer::div(
        get_string('dir_lowsample', 'local_targetzenit', (object) [
            'min' => reyting::MIN_NAMUNA,
            'ismlar' => implode(', ', $ismlar),
        ]), 'tz-dir-sub mt-2');
}
echo html_writer::end_div();

// ============================== KUCHLI VA RIVOJLANTIRISH KERAK
echo html_writer::start_div('tz-dir-two');

foreach ([
    ['dir_strongest', $xulosa['kuchli'], 'good'],
    ['dir_todevelop', $xulosa['kuchsiz'], 'serious'],
] as [$sarlavha, $royxat, $status]) {
    echo html_writer::start_div('tz-dir-card');
    echo html_writer::tag('h4', get_string($sarlavha, 'local_targetzenit'),
        ['class' => 'tz-dir-cardtitle']);
    echo html_writer::div(get_string('dir_vsownnorm', 'local_targetzenit'), 'tz-dir-sub mb-2');

    foreach ($royxat as $i => $k) {
        echo html_writer::div(
            html_writer::tag('span', (string) ($i + 1), ['class' => 'tz-dir-rank'])
            . html_writer::div(
                html_writer::div(s($k['nom']), 'tz-dir-chipname')
                . tz_bar($k['avg'], $k['norm']), 'tz-dir-qbody')
            . html_writer::div(
                html_writer::tag('b', $k['avg'] . '%')
                . html_writer::div(
                    ($k['delta'] >= 0 ? '+' : '') . $k['delta'] . '%',
                    'tz-dir-sub tz-dir-t-' . ($k['delta'] >= 0 ? 'pos' : 'neg'))),
            'tz-dir-prog');
    }
    echo html_writer::end_div();
}

echo html_writer::end_div();
