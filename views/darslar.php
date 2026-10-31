<?php
// «Darslar tahlili» ko'rinishi — direktor.php ichidan chaqiriladi.
//
// To'g'ridan-to'g'ri so'rov bilan ochilmaydi: config.php yuklanmagan bo'lsa
// MOODLE_INTERNAL ham aniqlanmagan bo'ladi.
defined('MOODLE_INTERNAL') || die();

$terms = timetable::terms($year);
$holidays = timetable::holidays($year);
$kunholat = timetable::school_day($date, $terms, $holidays);

$s = dashboard::today_summary($date);
$sanoq = dashboard::daily_counts($date);
$trend = dashboard::trend($date);

// ====================================================== SARLAVHA + FILTR
echo html_writer::start_div('tz-dir-head');
echo html_writer::div(
    html_writer::tag('h1', get_string('dir_lessonstitle', 'local_targetzenit'),
        ['class' => 'tz-dir-h1'])
    . html_writer::div(get_string('dir_lessonssub', 'local_targetzenit'), 'tz-dir-sub'));

echo html_writer::start_div('tz-dir-tools');
$oldin = date('Y-m-d', strtotime('-1 day', strtotime($date)));
$keyin = date('Y-m-d', strtotime('+1 day', strtotime($date)));
echo html_writer::link($url(['date' => $oldin]), '‹',
    ['class' => 'btn btn-sm btn-outline-secondary']);
echo html_writer::tag('span', userdate(strtotime($date), '%d %B, %A'), ['class' => 'tz-dir-date']);
echo html_writer::link($url(['date' => $keyin]), '›',
    ['class' => 'btn btn-sm btn-outline-secondary']);
if ($date !== date('Y-m-d')) {
    echo html_writer::link($url(['date' => date('Y-m-d')]),
        get_string('dir_today', 'local_targetzenit'),
        ['class' => 'btn btn-sm btn-outline-primary']);
}
echo html_writer::tag('span',
    ($kunholat['ok'] ? s($kunholat['reason']) : '⚠ ' . s($kunholat['reason'])),
    ['class' => 'tz-dir-meta']);
echo html_writer::end_div();
echo html_writer::end_div();

// ====================================================== STAT PLITALARI
$norma = ai::norm_overall();

$soat = intdiv($s->audiosoniya, 3600);
$daq = intdiv($s->audiosoniya % 3600, 60);

$tiles = tz_tile((string) $s->jami, get_string('dir_lessons_today', 'local_targetzenit'),
    get_string('dir_byschedule', 'local_targetzenit'));

$tiles .= tz_tile($soat . 's ' . $daq . 'daq',
    get_string('dir_totalaudio', 'local_targetzenit'),
    get_string('dir_nrecordings', 'local_targetzenit', $s->yozuv),
    tz_spark(array_column($trend, 'yozuv')));

// Sifat ko'rsatkichi — MUTLAQ foiz, lekin izohda normaga nisbati turadi.
// Maketda bu yerda faqat mutlaq foiz bor edi va u chalg'itardi.
if ($s->ball === null) {
    $tiles .= tz_tile('—', get_string('dir_quality', 'local_targetzenit'),
        get_string('dir_noanalysis', 'local_targetzenit'));
} else {
    $d = $s->delta;
    $tiles .= tz_tile($s->ball . '%', get_string('dir_quality', 'local_targetzenit'),
        get_string('dir_vsnormshort', 'local_targetzenit',
            (object) ['delta' => ($d >= 0 ? '+' : '') . $d, 'norma' => $norma, 'n' => $s->delta_n]),
        // Sparkline ATAYLAB yo'q: `trend()` jurnal to'ldirilish foizini
        // qaytaradi, plita esa sifat ballini ko'rsatadi. Boshqa o'lchovning
        // chizig'ini raqam ostiga qo'yish chalg'itadi.
        '',
        $d >= 0 ? 'pos' : 'neg');
}

$tiles .= tz_tile((string) $sanoq['yozmadi'], get_string('dir_notsent', 'local_targetzenit'),
    get_string('dir_sendreminder', 'local_targetzenit'), '',
    $sanoq['yozmadi'] ? 'neg' : '');

echo html_writer::div($tiles, 'tz-dir-hero');

// ====================================== BUGUNGI YOZUVLAR NAZORATI
echo html_writer::start_div('tz-dir-card');
echo html_writer::start_div('tz-dir-head');
echo html_writer::div(
    html_writer::tag('h4', get_string('dir_control', 'local_targetzenit'),
        ['class' => 'tz-dir-cardtitle'])
    . html_writer::div(get_string('dir_controlsub', 'local_targetzenit'), 'tz-dir-sub'));

// Sanoqlar — ayni paytda FILTR tugmalari
echo html_writer::start_div('tz-dir-tools');
foreach ([
    'all' => [$sanoq['jami'], get_string('dir_fall', 'local_targetzenit')],
    'recorded' => [$sanoq['yozdi'], get_string('dir_frec', 'local_targetzenit')],
    'missing' => [$sanoq['yozmadi'], get_string('dir_fmiss', 'local_targetzenit')],
    'upcoming' => [$sanoq['kutilmoqda'], get_string('dir_fsoon', 'local_targetzenit')],
] as $k => [$son, $nom]) {
    echo html_writer::link($url(['filter' => $k]),
        html_writer::tag('b', (string) $son) . ' ' . s($nom),
        ['class' => 'tz-dir-tab' . ($filter === $k ? ' tz-on' : '')]);
}
echo html_writer::end_div();
echo html_writer::end_div();

// Kartachalar: YOZMAGANLAR birinchi — direktorga kerak bo'lgani shu
$nazorat = dashboard::daily_lessons($date, 'missing', 0, 8);
$bosh = 8 - count($nazorat['rows']);
$qoshimcha = $bosh > 0 ? dashboard::daily_lessons($date, 'recorded', 0, $bosh)['rows'] : [];
$kartalar = array_merge($nazorat['rows'], $qoshimcha);

if (!$kartalar) {
    echo html_writer::div(get_string('dir_nolessons', 'local_targetzenit'), 'tz-dir-sub mt-2');
} else {
    echo html_writer::start_div('tz-dir-chips mt-2');
    foreach ($kartalar as $k) {
        $ichi = tz_av($k->ism);
        $ong = html_writer::div(s($k->ism), 'tz-dir-chipname')
            . html_writer::div(s(trim($k->subjectname . ' · ' . $k->groupname, ' ·')), 'tz-dir-sub')
            . html_writer::div(
                $k->yozuvbor
                    ? tz_chip('good', '✓ ' . get_string('dir_recorded', 'local_targetzenit'))
                    : tz_chip('critical', '✗ ' . get_string('dir_notrecorded', 'local_targetzenit')),
                'mt-1');
        $ichi .= html_writer::div($ong, 'tz-dir-qbody');
        $ichi .= html_writer::div(s((string) $k->starttime), 'tz-dir-chiptime');
        echo html_writer::div($ichi,
            'tz-dir-chipcard' . ($k->yozuvbor ? '' : ' tz-miss'));
    }
    echo html_writer::end_div();

    if ($sanoq['yozmadi'] > count($nazorat['rows'])) {
        echo html_writer::div(html_writer::link($url(['filter' => 'missing']),
            get_string('dir_seemissing', 'local_targetzenit', $sanoq['yozmadi']) . ' →'),
            'mt-2');
    }
}
echo html_writer::end_div();

// ====================================================== DIQQAT NAVBATI
// Maketda bu blok yo'q edi, lekin u panelning eng foydali qismi: direktor
// qidirmaydi, unga ustuvorlik tartibida ko'rsatiladi.
$a = dashboard::attention($date);
$items = $a['items'] ?? [];
if ($items) {
    echo html_writer::start_div('tz-dir-card');
    echo html_writer::tag('h4',
        '⚠ ' . get_string('dir_attention', 'local_targetzenit')
        . html_writer::tag('span', ' (' . count($items) . ')', ['class' => 'tz-dir-count']),
        ['class' => 'tz-dir-cardtitle']);
    echo html_writer::div(
        get_string('dir_attention_window', 'local_targetzenit',
            userdate(strtotime($a['oyna_boshi'] ?? $date), '%d.%m')), 'tz-dir-sub mb-2');

    echo html_writer::start_div('tz-dir-queue');
    foreach ($items as $it) {
        $chap = $it->lessonno
            ? trim(($it->starttime ?? '') . ' · ' . $it->lessonno . '-dars')
            : userdate(strtotime($it->lessondate), '%d.%m');
        $amal = $it->slotid
            ? html_writer::link(new moodle_url('/local/targetzenit/dars_tahlil.php',
                ['slotid' => $it->slotid]), get_string('dir_openlesson', 'local_targetzenit'),
                ['class' => 'btn btn-sm btn-outline-secondary'])
            : '';
        echo html_writer::div(
            html_writer::div(tz_chip($it->status, $it->kind), 'tz-dir-qchip')
            . html_writer::div(
                html_writer::div(s($chap) . ' · ' . s($it->title), 'tz-dir-qtitle')
                . html_writer::div(s($it->detail), 'tz-dir-sub'), 'tz-dir-qbody')
            . html_writer::div($amal, 'tz-dir-qact'),
            'tz-dir-qrow tz-dir-l' . $it->level);
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
}

// ====================================================== DARS YOZUVLARI
$list = dashboard::daily_lessons($date, $filter, $page, TZ_PER_PAGE, $qidiruv);

echo html_writer::start_div('tz-dir-card');
echo html_writer::start_div('tz-dir-head');
echo html_writer::div(
    html_writer::tag('h4', get_string('dir_lessonlist', 'local_targetzenit'),
        ['class' => 'tz-dir-cardtitle'])
    . html_writer::div(get_string('dir_showing', 'local_targetzenit',
        (object) ['n' => count($list['rows']), 'jami' => $list['jami']]), 'tz-dir-sub'));

echo html_writer::start_div('tz-dir-tools');
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline']);
foreach (['view' => $view, 'date' => $date, 'filter' => $filter] as $k => $v) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $k, 'value' => $v]);
}
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'q', 'value' => $qidiruv,
    'class' => 'form-control form-control-sm mr-2',
    'placeholder' => get_string('dir_search', 'local_targetzenit')]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-sm btn-outline-secondary',
    'value' => get_string('dir_searchbtn', 'local_targetzenit')]);
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

if (!$list['rows']) {
    echo html_writer::div(get_string('dir_nolessons', 'local_targetzenit'), 'tz-dir-sub');
} else {
    echo html_writer::start_div('tz-dir-tablewrap');
    echo html_writer::start_tag('table', ['class' => 'tz-dir-table']);
    echo html_writer::start_tag('thead') . html_writer::start_tag('tr');
    foreach (['dir_colteacher', 'dir_collesson', 'dir_coltime', 'dir_colscore',
              'dir_colstatus'] as $k) {
        echo html_writer::tag('th', get_string($k, 'local_targetzenit'));
    }
    echo html_writer::tag('th', '');
    echo html_writer::end_tag('tr') . html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($list['rows'] as $r) {
        $havola = new moodle_url('/local/targetzenit/dars_tahlil.php', ['slotid' => $r->slotid]);

        echo html_writer::start_tag('tr');

        echo html_writer::tag('td', html_writer::div(
            tz_av($r->ism) . html_writer::div(
                html_writer::div(s($r->ism), 'tz-dir-chipname')
                . html_writer::div(s((string) $r->subjectname), 'tz-dir-sub')),
            'tz-dir-cellname'));

        echo html_writer::tag('td',
            html_writer::tag('b', s((string) $r->groupname))
            . html_writer::div(s((string) ($r->topicshort ?? '')), 'tz-dir-sub'));

        $davom = $r->audioseconds
            ? gmdate($r->audioseconds >= 3600 ? 'H:i:s' : 'i:s', (int) $r->audioseconds) : '—';
        echo html_writer::tag('td',
            s(trim($r->starttime . '–' . $r->endtime, '–'))
            . html_writer::div($r->yozuvbor ? '♪ ' . s($davom)
                : ($r->otgan ? get_string('dir_norec', 'local_targetzenit')
                             : get_string('dir_notyet', 'local_targetzenit')), 'tz-dir-sub'));

        echo html_writer::tag('td', tz_donut(
            $r->overallscore === null ? null : (int) $r->overallscore));

        // Holat NORMAGA NISBATAN: 61% — "O'rtacha", "E'tibor kerak" emas
        echo html_writer::tag('td',
            $r->overallscore === null
                ? ($r->analysestatus === 'failed'
                    ? tz_chip('warning', get_string('dir_failed', 'local_targetzenit'))
                    : tz_chip('none', get_string('dir_noanalysisshort', 'local_targetzenit')))
                : tz_chip($r->band['status'], $r->band['nom'],
                    get_string('dir_normis', 'local_targetzenit', $norma)));

        echo html_writer::tag('td',
            html_writer::link($havola, '›', ['class' => 'tz-dir-rowlink']));

        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody') . html_writer::end_tag('table');
    echo html_writer::end_div();

    echo $OUTPUT->paging_bar($list['jami'], $page, TZ_PER_PAGE,
        $url(['filter' => $filter, 'q' => $qidiruv]));
}
echo html_writer::end_div();
