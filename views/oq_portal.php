<?php
// «Mening darslarim» — PORTAL ko'rinishi (o'qituvchi, maket qobig'ida).
// oqituvchi.php ichidan FAQAT $portal=true bo'lganda chaqiriladi; direktor
// ko'rinishi oq_hisob.php da qoladi. Maket: ~/Documents/oqituvchi-portal-ui.html
// «darslar» bo'limi. Ma'lumot: teacher:: (summary/lessons/breakdown/profile).
defined('MOODLE_INTERNAL') || die();

use local_targetzenit\dashboard;
use local_targetzenit\output\ui;
use local_targetzenit\teacher;

global $DB, $OUTPUT;

// Bo'sh davr — sababini aytib to'xtaymiz (oqituvchi.php .tz-oq va $tugat yopadi).
if (!$hisob->jami) {
    echo $OUTPUT->notification(get_string('oq_empty', 'local_targetzenit'), 'info');
    if ($davrkod !== 'hammasi') {
        echo html_writer::div(html_writer::link($url(['davr' => 'hammasi', 'page' => 0]),
            get_string('oq_tryall', 'local_targetzenit')), 'mb-3');
    }
    return;
}

global $PAGE;
$kesim = teacher::breakdown($userid, $davr);

// --------------------------------------------------- SARLAVHA + tugma
echo html_writer::start_div('tz-oqp-head');
echo html_writer::div(
    html_writer::tag('div', get_string('oq_mypanel', 'local_targetzenit'), ['class' => 'tzp-h1'])
    . html_writer::tag('div', get_string('oq_periodis', 'local_targetzenit', (object) [
        'nom' => $davr['nom'],
        'from' => userdate(strtotime($davr['from']), '%d-%b'),
        'to' => userdate(strtotime($davr['to']), '%d-%b'),
    ]), ['class' => 'tzp-sub']));
echo html_writer::link($url(['view' => 'yozuvlar']),
    '📊 ' . get_string('oqp_analyses', 'local_targetzenit') . ' →',
    ['class' => 'btn btn-sm btn-primary']);
echo html_writer::end_div();

// --------------------------------------------------- DAVR + GURUH FILTRI
echo html_writer::start_div('tz-oqp-filter');
echo html_writer::tag('b', get_string('oqp_period_lbl', 'local_targetzenit') . ':',
    ['class' => 'tz-flbl']);
echo html_writer::start_div('tz-seg');
foreach (teacher::periods() as $k => $str) {
    echo html_writer::link($url(['davr' => $k, 'page' => 0]),
        get_string($str, 'local_targetzenit'),
        ['class' => 'tz-seg-b' . ($davrkod === $k ? ' tz-on' : '')]);
}
echo html_writer::end_div();
if ($kesim) {
    $sel = [];
    $allurl = $url(['gk' => '', 'sk' => '', 'page' => 0])->out(false);
    $sel[$allurl] = get_string('oqp_allgroups', 'local_targetzenit');
    $curval = $allurl;
    foreach ($kesim as $kk2) {
        $u = $url(['gk' => $kk2->groupkey, 'sk' => $kk2->subjectkey, 'page' => 0])->out(false);
        $sel[$u] = $kk2->groupname . ' · ' . $kk2->subjectname;
        if ($groupkey === $kk2->groupkey && $subjectkey === $kk2->subjectkey) {
            $curval = $u;
        }
    }
    echo html_writer::tag('b', get_string('oqp_group_lbl', 'local_targetzenit') . ':',
        ['class' => 'tz-flbl tz-flbl-r']);
    echo html_writer::select($sel, 'guruhsel', $curval, false,
        ['class' => 'form-control form-control-sm tz-oqp-gsel', 'id' => 'tz-oqp-gsel']);
    $PAGE->requires->js_amd_inline("
        require([], function() {
            var s = document.getElementById('tz-oqp-gsel');
            if (s) { s.addEventListener('change', function() { window.location.href = s.value; }); }
        });");
}
echo html_writer::end_div();

// --------------------------------------------------- STAT PLITALARI
$toliq = (int) ($sanoq['toliq'] ?? 0);
$kamchilik = (int) ($sanoq['nuqson'] ?? 0);
// Plita = filtr havolasi: bosilganda pastdagi dars ro'yxati o'sha turga filtrlanadi.
$stat = function(string $num, string $lab, string $sub, string $cls, $href) use ($filter) {
    $inner = html_writer::tag('span', $num, ['class' => 'tz-stat-num'])
        . html_writer::tag('span', $lab, ['class' => 'tz-stat-lab'])
        . ($sub !== '' ? html_writer::tag('span', $sub, ['class' => 'tz-stat-sub']) : '');
    return html_writer::link($href, $inner,
        ['class' => 'tz-stat tz-stat-link ' . $cls]);
};
$aiplit = '—';
$aisub = get_string('dir_noanalysis', 'local_targetzenit');
$aicls = '';
if ($hisob->ball !== null) {
    $d = (int) $hisob->ball - $norma;
    $aiplit = ($d >= 0 ? '+' : '') . $d;
    $aisub = get_string($d >= 0 ? 'oqp_abovenorm' : 'oqp_belownorm', 'local_targetzenit');
    $aicls = 'tz-stat-ai';
}
echo html_writer::div(
    $stat((string) $hisob->jami, get_string('oqp_t_total', 'local_targetzenit'), '', '',
        $url(['filter' => 'all', 'page' => 0]))
    . $stat((string) $toliq, get_string('oqp_t_done', 'local_targetzenit'),
        ($hisob->tolik === null ? '' : $hisob->tolik . '%'), 'tz-stat-ok',
        $url(['filter' => 'toliq', 'page' => 0]))
    . $stat((string) $kamchilik, get_string('oqp_t_gap', 'local_targetzenit'), '',
        $kamchilik ? 'tz-stat-warn' : '', $url(['filter' => 'nuqson', 'page' => 0]))
    . $stat($aiplit, get_string('oqp_t_ai', 'local_targetzenit'), $aisub, $aicls,
        $url(['filter' => 'tahlilli', 'page' => 0])),
    'tz-statgrid');

// --------------------------------------------------- IKKI USTUN
echo html_writer::start_div('tz-oqp-cols');

// ======================================== CHAP USTUN
echo html_writer::start_div('tz-oqp-main');

// --- BUGUN uchrashuv (agar bugunga belgilangan bo'lsa)
$t0 = strtotime('today');
$uchrlar = $DB->get_records_sql("
    SELECT u.*
      FROM {local_targetzenit_uchrashuv} u
      JOIN {message_conversation_members} m ON m.conversationid = u.conversationid
     WHERE m.userid = :me AND u.sana >= :a AND u.sana <= :b AND u.holat <> 'bekor'
  ORDER BY u.sana", ['me' => $userid, 'a' => $t0, 'b' => $t0 + 86399]);
foreach ($uchrlar as $u) {
    echo html_writer::div(
        html_writer::tag('b', '📅 ' . get_string('oqp_today_meet', 'local_targetzenit'))
        . ' — ' . userdate((int) $u->sana, '%H:%M')
        . ((string) $u->joy !== '' ? ' · ' . s($u->joy) : '')
        . ' · ' . html_writer::link(
            new moodle_url('/local/targetzenit/muloqot.php', ['conv' => (int) $u->conversationid]),
            get_string('oqp_open_chat', 'local_targetzenit') . ' →'),
        'tz-meetnote');
}

// --- Bajarilishi kerak
$todo = [];
if ($kamchilik) {
    $todo[] = ['r', $kamchilik, get_string('oqp_todo_gap', 'local_targetzenit'),
        $url(['filter' => 'nuqson', 'page' => 0])];
}
if ((int) ($sanoq['yozuvsiz'] ?? 0)) {
    $todo[] = ['a', (int) $sanoq['yozuvsiz'], get_string('oqp_todo_rec', 'local_targetzenit'),
        $url(['filter' => 'yozuvsiz', 'page' => 0])];
}
if ((int) ($sanoq['kelgusi'] ?? 0)) {
    $todo[] = ['b', (int) $sanoq['kelgusi'], get_string('oqp_todo_soon', 'local_targetzenit'),
        $url(['filter' => 'kelgusi', 'page' => 0])];
}
// Kelayotgan uchrashuvlar (bugundan keyin) — chatga havola bilan.
$kelasi = $DB->get_records_sql("
    SELECT u.* FROM {local_targetzenit_uchrashuv} u
      JOIN {message_conversation_members} m ON m.conversationid = u.conversationid
     WHERE m.userid = :me AND u.sana > :now AND u.holat <> 'bekor'
  ORDER BY u.sana", ['me' => $userid, 'now' => $t0 + 86399], 0, 3);
foreach ($kelasi as $uk) {
    $sorov = ($uk->holat === 'kutilmoqda' && (int) $uk->taklifuserid !== (int) $userid);
    $todo[] = ['b', $sorov ? '✉' : '📅',
        ($sorov ? get_string('oqp_meet_req', 'local_targetzenit')
            : get_string('oqp_meet_next', 'local_targetzenit'))
            . ' — ' . userdate((int) $uk->sana, '%d-%b %H:%M'),
        new moodle_url('/local/targetzenit/muloqot.php', ['conv' => (int) $uk->conversationid])];
}
if ($todo) {
    echo html_writer::start_div('tzp-card');
    echo html_writer::tag('div', get_string('oqp_todo', 'local_targetzenit'), ['class' => 'tzp-h3']);
    echo html_writer::start_tag('ul', ['class' => 'tz-todo']);
    foreach ($todo as [$cls, $n, $lab, $lnk]) {
        echo html_writer::tag('li',
            html_writer::tag('span', $n, ['class' => 'tz-todo-n tz-todo-' . $cls])
            . html_writer::div(html_writer::link($lnk, s($lab))));
    }
    echo html_writer::end_tag('ul');
    echo html_writer::end_div();
}

// --- Dars-dars ro'yxati
$list = teacher::lessons($userid, $davr, $filter, $page, teacher::QATOR,
    ['groupkey' => $groupkey, 'subjectkey' => $subjectkey]);

echo html_writer::start_div('tzp-card');
echo html_writer::div(
    get_string('oq_lessons', 'local_targetzenit')
    . ' ' . html_writer::tag('span', (int) $list['jami'], ['class' => 'tzp-cnt']),
    'tzp-h3');

// Filtr chiplari
echo html_writer::start_div('tz-dlfilter');
foreach (['all' => 'dir_fall', 'toliq' => 'oqp_t_done', 'nuqson' => 'oq_fgap',
          'yozuvsiz' => 'oq_fnorec', 'tahlilli' => 'oq_fanalysed',
          'kelgusi' => 'dir_fsoon'] as $k => $str) {
    echo html_writer::link($url(['filter' => $k, 'page' => 0]),
        s(get_string($str, 'local_targetzenit')) . ' (' . (int) ($sanoq[$k] ?? 0) . ')',
        ['class' => 'tz-chip' . ($filter === $k ? ' tz-on' : '')]);
}
echo html_writer::end_div();

if (!$list['rows']) {
    echo html_writer::div(get_string('dir_nolessons', 'local_targetzenit'), 'tzp-empty');
} else {
    // Kokpit havolasi uchun ruxsat (keshlangan).
    $teachkesh = [];
    $teachable = function(int $courseid) use (&$teachkesh): bool {
        if (!$courseid) {
            return false;
        }
        if (!array_key_exists($courseid, $teachkesh)) {
            $ctx = context_course::instance($courseid, IGNORE_MISSING);
            $teachkesh[$courseid] = $ctx && has_capability('local/targetzenit:teach', $ctx);
        }
        return $teachkesh[$courseid];
    };
    $c = function(bool $ok, string $lab, bool $otgan = true) {
        if (!$otgan) {
            return html_writer::tag('span', $lab . ' —', ['class' => 'tz-c tz-c-wt']);
        }
        return html_writer::tag('span', $lab . ' ' . ($ok ? '✓' : '✗'),
            ['class' => 'tz-c ' . ($ok ? 'tz-c-ok' : 'tz-c-no')]);
    };

    foreach ($list['rows'] as $r) {
        $otgan = (bool) $r->otgan;
        $hastopic = (bool) $r->hastopic;
        $davomat = (int) $r->attendancecount > 0;
        $core = $hastopic && $davomat && $r->yozuvbor;
        $empty = !$hastopic && (int) $r->attendancecount === 0;
        $rowcls = !$otgan ? '' : ($core ? 'tz-dl-done' : ($empty ? 'tz-dl-miss' : 'tz-dl-part'));

        // Sana
        $date = html_writer::tag('b', userdate(strtotime($r->lessondate), '%d-%b'))
            . html_writer::tag('small', s(trim(((string) $r->starttime) . ' · '
                . $r->lessonno . '-para', ' ·')));

        // Guruh · dars + mavzu
        $main = html_writer::div(
            html_writer::tag('b', s((string) $r->groupname)) . ' · ' . s((string) $r->subjectname)
            . ((string) $r->room !== '' ? ' '
                . html_writer::tag('span', '· ' . s((string) $r->room),
                    ['class' => 'text-muted']) : ''), 'tz-dl-grp');
        $mavzu = trim((string) $r->topicshort);
        $main .= html_writer::div($mavzu !== '' ? s($mavzu)
            : get_string('dir_notopic', 'local_targetzenit'), 'tz-dl-topic');

        // 4 mezon
        $st = $c($hastopic, get_string('oq_c_topic', 'local_targetzenit'), $otgan)
            . $c($davomat, get_string('oq_c_att', 'local_targetzenit'), $otgan)
            . $c((bool) $r->yozuvbor, get_string('oq_c_rec', 'local_targetzenit'), $otgan)
            . $c((bool) $r->hashomework, get_string('oq_c_hw', 'local_targetzenit'), $otgan);
        $main .= html_writer::div($st, 'tz-dl-st');

        // AI qisqa
        if ($r->overallscore !== null) {
            $d = (int) $r->overallscore - $norma;
            $aimini = s($r->band['nom']) . ' · ' . ($d >= 0 ? '+' : '') . $d
                . ' ' . get_string('dir_normis', 'local_targetzenit', $norma);
        } else if ($r->analysestatus === 'pending') {
            $aimini = get_string('ai_pending', 'local_targetzenit');
        } else if ($otgan && !$r->yozuvbor) {
            $aimini = get_string('oqp_ai_norec', 'local_targetzenit');
        } else if ($r->analysestatus === 'failed') {
            $aimini = get_string('dir_failed', 'local_targetzenit');
        } else {
            $aimini = '';
        }
        if ($aimini !== '') {
            $main .= html_writer::div(
                html_writer::tag('span', 'AI', ['class' => 'tz-aibadge']) . ' ' . $aimini,
                'tz-dl-ai');
        }

        // Status pill (maketdagidek): bajarildi / qisman / qolgan / kelgusi.
        if (!$otgan) {
            $pill = ['wt', 'oqp_s_future'];
        } else if ($core) {
            $pill = ['g', 'oqp_s_done'];
        } else if ($empty) {
            $pill = ['r', 'oqp_s_miss'];
        } else {
            $pill = ['a', 'oqp_s_part'];
        }
        $pillhtml = html_writer::tag('span', get_string($pill[1], 'local_targetzenit'),
            ['class' => 'tz-spill tz-spill-' . $pill[0]]);

        // Amal (kokpit)
        if ($teachable((int) $r->courseid)) {
            $act = html_writer::link(new moodle_url('/local/targetzenit/dars.php', [
                'courseid' => (int) $r->courseid, 'date' => $r->lessondate,
                'lessonno' => (int) $r->lessonno]),
                ($r->lessonid ? get_string('oq_open', 'local_targetzenit')
                    : get_string('oq_fill', 'local_targetzenit')) . ' →',
                ['class' => 'btn btn-sm btn-outline-primary']);
        } else {
            $act = html_writer::tag('span', '—', ['class' => 'text-muted']);
        }

        echo html_writer::div(
            html_writer::div($date, 'tz-dl-date')
            . html_writer::div($main, 'tz-dl-main')
            . html_writer::div($pillhtml . $act, 'tz-dl-act'),
            'tz-dlrow ' . $rowcls);
    }
    echo $OUTPUT->paging_bar($list['jami'], $page, teacher::QATOR, $url());
}
echo html_writer::end_div();   // tzp-card (darslar)
echo html_writer::end_div();   // tz-oqp-main

// ======================================== O'NG USTUN
echo html_writer::start_div('tz-oqp-side');

// --- Mezonlar bo'yicha bajarilish
echo html_writer::start_div('tzp-card');
echo html_writer::tag('div', get_string('oqp_meters', 'local_targetzenit'), ['class' => 'tzp-h3']);
foreach ([
    ['oq_m_topic', $hisob->mavzu],
    ['oq_m_att', $hisob->yoqlama],
    ['oq_m_hw', $hisob->vazifa],
    ['oq_m_rec', $hisob->yozuv],
] as [$str, $foiz]) {
    echo html_writer::div(
        html_writer::div(
            html_writer::tag('span', get_string($str, 'local_targetzenit'))
            . html_writer::tag('b', $foiz === null ? '—' : $foiz . '%'), 'tz-meter-lab')
        . ui::bar($foiz === null ? null : (float) $foiz, null), 'tz-meter');
}
echo html_writer::end_div();

// --- AI umumiy tahlili
$profil = teacher::profile($userid, $davr);
$kk = teacher::kuchli_kuchsiz($profil);
if ($kk['kuchli']) {
    echo html_writer::start_div('tzp-card');
    echo html_writer::tag('div', get_string('oqp_ai_overall', 'local_targetzenit'), ['class' => 'tzp-h3']);
    echo html_writer::div(
        html_writer::tag('span', get_string('oq_strong', 'local_targetzenit'))
        . html_writer::tag('b', s($kk['kuchli']->nom) . ' +' . $kk['kuchli']->delta,
            ['style' => 'color:#15803d']), 'tz-kv');
    echo html_writer::div(
        html_writer::tag('span', get_string('oq_weak', 'local_targetzenit'))
        . html_writer::tag('b', s($kk['kuchsiz']->nom) . ' '
            . ($kk['kuchsiz']->delta >= 0 ? '+' : '') . $kk['kuchsiz']->delta,
            ['style' => 'color:#b45309']), 'tz-kv');
    echo html_writer::end_div();
}

// --- Guruhlar bo'yicha ($kesim yuqorida hisoblangan)
if ($kesim) {
    echo html_writer::start_div('tzp-card');
    echo html_writer::tag('div', get_string('oqp_bygroup', 'local_targetzenit'), ['class' => 'tzp-h3']);
    foreach ($kesim as $k) {
        echo html_writer::div(
            html_writer::tag('span', s($k->groupname) . ' · ' . s($k->subjectname))
            . html_writer::tag('b', (int) $k->otgan . ' / ' . (int) $k->jami), 'tz-kv');
    }
    echo html_writer::end_div();
}

// --- Davr xulosasi
$soat = intdiv((int) $hisob->audiosoniya, 3600);
$daq = intdiv((int) $hisob->audiosoniya % 3600, 60);
echo html_writer::start_div('tzp-card');
echo html_writer::tag('div', get_string('oqp_period', 'local_targetzenit'), ['class' => 'tzp-h3']);
echo html_writer::div(html_writer::tag('span', get_string('oq_done', 'local_targetzenit'))
    . html_writer::tag('b', (int) $hisob->otgan . ' / ' . (int) $hisob->jami), 'tz-kv');
echo html_writer::div(html_writer::tag('span', get_string('dir_totalaudio', 'local_targetzenit'))
    . html_writer::tag('b', $soat . 's ' . $daq . 'daq · ' . (int) $hisob->yozuvsoni), 'tz-kv');
if ($hisob->ball !== null) {
    echo html_writer::div(html_writer::tag('span', get_string('dir_quality', 'local_targetzenit'))
        . html_writer::tag('b', $hisob->ball . '%'), 'tz-kv');
}
if ($hisob->orninbosdi) {
    echo html_writer::div(html_writer::tag('span', get_string('oqp_subst', 'local_targetzenit'))
        . html_writer::tag('b', (int) $hisob->orninbosdi), 'tz-kv');
}
echo html_writer::end_div();

echo html_writer::end_div();   // tz-oqp-side
echo html_writer::end_div();   // tz-oqp-cols
