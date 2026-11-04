<?php
// «Umumiy hisob» ko'rinishi — oqituvchi.php ichidan chaqiriladi.
//
// To'g'ridan-to'g'ri so'rov bilan ochilmaydi: config.php yuklanmagan bo'lsa
// MOODLE_INTERNAL ham aniqlanmagan bo'ladi.
defined('MOODLE_INTERNAL') || die();

// DIQQAT: `use` ifodalari PHP'da FAYLGA XOS va `require` orqali O'TMAYDI —
// oqituvchi.php dagi `use` lar bu yerda ishlamaydi.
use local_targetzenit\ai;
use local_targetzenit\dashboard;
use local_targetzenit\output\ui;
use local_targetzenit\teacher;

// Davrda umuman dars yo'q — bo'sh plitalar va jadvallar chalg'itadi,
// shuning uchun sababini aytib to'xtaymiz. Eng ko'p uchraydigan sabab:
// `_staff` kaliti nomuvofiq (Employee ID user.idnumber bilan bog'lanmagan),
// ya'ni darslar bu odamga YOZILMAGAN.
if (!$hisob->jami) {
    echo $OUTPUT->notification(get_string('oq_empty', 'local_targetzenit'), 'info');
    if ($davrkod !== 'hammasi') {
        echo html_writer::div(html_writer::link($url(['davr' => 'hammasi', 'page' => 0]),
            get_string('oq_tryall', 'local_targetzenit')), 'mb-3');
    }
    echo html_writer::end_div();          // .tz-oq
    \local_targetzenit\output\shell::end();
    echo $OUTPUT->footer();
    exit;
}

// ====================================================== STAT PLITALARI
$soat = intdiv($hisob->audiosoniya, 3600);
$daq = intdiv($hisob->audiosoniya % 3600, 60);

$tiles = ui::tile((string) $hisob->otgan, get_string('oq_done', 'local_targetzenit'),
    get_string('oq_ofplanned', 'local_targetzenit', $hisob->jami));

$tiles .= ui::tile($hisob->tolik === null ? '—' : $hisob->tolik . '%',
    get_string('oq_complete', 'local_targetzenit'),
    get_string('oq_complete_sub', 'local_targetzenit'), '',
    $hisob->tolik === null ? '' : ($hisob->tolik >= 90 ? 'pos' : 'neg'));

$tiles .= ui::tile($soat . 's ' . $daq . 'daq', get_string('dir_totalaudio', 'local_targetzenit'),
    get_string('dir_nrecordings', 'local_targetzenit', $hisob->yozuvsoni));

if ($hisob->ball === null) {
    $tiles .= ui::tile('—', get_string('dir_quality', 'local_targetzenit'),
        get_string('dir_noanalysis', 'local_targetzenit'));
} else {
    $d = $hisob->ball - $norma;
    $tiles .= ui::tile($hisob->ball . '%', get_string('dir_quality', 'local_targetzenit'),
        get_string('dir_vsnormshort', 'local_targetzenit', (object) [
            'delta' => ($d >= 0 ? '+' : '') . $d, 'norma' => $norma, 'n' => $hisob->tahlil]),
        ui::spark(teacher::trend($userid, $davr)),
        $d >= 0 ? 'pos' : 'neg');
}

echo html_writer::div($tiles, 'tz-dir-hero');

// ====================================== MAJBURIY VAZIFALAR (meter qatori)
echo html_writer::start_div('tz-dir-card');
echo html_writer::div(
    html_writer::tag('h4', get_string('oq_duties', 'local_targetzenit'),
        ['class' => 'tz-dir-cardtitle'])
    . html_writer::div(get_string('oq_duties_sub', 'local_targetzenit'), 'tz-dir-sub'),
    'tz-dir-head');

// Baho ATAYLAB oxirgi va alohida izoh bilan: u majburiy uchlikka kirmaydi
// (6-tamoyil), aks holda baho qo'yilmagan normal dars "bajarilmagan"
// bo'lib chiqardi.
$olchov = [
    ['oq_m_topic', $hisob->mavzu, true],
    ['oq_m_att', $hisob->yoqlama, true],
    ['oq_m_rec', $hisob->yozuv, true],
    ['oq_m_hw', $hisob->vazifa, false],
    ['oq_m_grade', $hisob->baho, false],
];
echo html_writer::start_div('tz-oq-meters');
foreach ($olchov as [$str, $foiz, $majburiy]) {
    $nom = get_string($str, 'local_targetzenit');
    $h = html_writer::div(
        html_writer::tag('span', s($nom), ['class' => 'tz-oq-mname'])
        . html_writer::tag('span', $foiz === null ? '—' : $foiz . '%',
            ['class' => 'tz-oq-mval']), 'tz-oq-mhead');
    // Majburiy o'lchov uchun kutilish 100%, shuning uchun norma belgisi yo'q
    $h .= ui::bar($foiz === null ? null : (float) $foiz, null);
    if (!$majburiy) {
        $h .= html_writer::div(get_string('oq_notrequired', 'local_targetzenit'), 'tz-dir-sub');
    }
    echo html_writer::div($h, 'tz-oq-meter' . ($majburiy ? ' tz-oq-must' : ''));
}
echo html_writer::end_div();

if ($hisob->orninbosdi) {
    echo html_writer::div(
        get_string('oq_substituted', 'local_targetzenit', $hisob->orninbosdi),
        'tz-dir-sub mt-2');
}
echo html_writer::end_div();

// ====================================================== GURUH × FAN
$kesim = teacher::breakdown($userid, $davr);
if ($kesim) {
    echo html_writer::start_div('tz-dir-card');
    echo html_writer::tag('h4', get_string('oq_bygroup', 'local_targetzenit'),
        ['class' => 'tz-dir-cardtitle']);
    echo html_writer::start_div('tz-dir-tablewrap');
    echo html_writer::start_tag('table', ['class' => 'tz-dir-table']);
    echo html_writer::start_tag('thead') . html_writer::start_tag('tr');
    foreach (['oq_colgroup', 'oq_colsubject', 'oq_collessons', 'oq_colcomplete',
              'dir_colscore'] as $k) {
        echo html_writer::tag('th', get_string($k, 'local_targetzenit'));
    }
    echo html_writer::end_tag('tr') . html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    foreach ($kesim as $k) {
        $tanlangan = ($groupkey === $k->groupkey && $subjectkey === $k->subjectkey);
        echo html_writer::start_tag('tr', $tanlangan ? ['class' => 'tz-on'] : []);
        echo html_writer::tag('td', html_writer::link(
            $url(['gk' => $tanlangan ? '' : $k->groupkey,
                  'sk' => $tanlangan ? '' : $k->subjectkey, 'page' => 0]),
            html_writer::tag('b', s($k->groupname))));
        echo html_writer::tag('td', s($k->subjectname));
        echo html_writer::tag('td', (int) $k->otgan . ' / ' . (int) $k->jami);
        echo html_writer::tag('td',
            ($k->tolik === null ? '—' : $k->tolik . '%') . ui::bar(
                $k->tolik === null ? null : (float) $k->tolik, null));
        echo html_writer::tag('td', $k->ball === null
            ? html_writer::tag('span', '—', ['class' => 'tz-dir-sub'])
            : ui::chip(dashboard::band($k->ball)['status'],
                $k->ball . '% · n=' . $k->tahlil));
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody') . html_writer::end_tag('table');
    echo html_writer::end_div();
    if ($groupkey !== '' || $subjectkey !== '') {
        echo html_writer::div(html_writer::link($url(['gk' => '', 'sk' => '', 'page' => 0]),
            get_string('oq_clearfilter', 'local_targetzenit')), 'mt-2');
    }
    echo html_writer::end_div();
}

// ====================================================== KATEGORIYA PROFILI
$profil = teacher::profile($userid, $davr);
$bor = array_filter($profil, fn($p) => $p->delta !== null);

echo html_writer::start_div('tz-dir-card');
echo html_writer::div(
    html_writer::tag('h4', get_string('oq_profile', 'local_targetzenit'),
        ['class' => 'tz-dir-cardtitle'])
    . html_writer::div(get_string('oq_profile_sub', 'local_targetzenit'), 'tz-dir-sub'),
    'tz-dir-head');

if (!$bor) {
    echo html_writer::div(get_string('oq_noprofile', 'local_targetzenit'), 'tz-dir-sub');
} else {
    $kk = teacher::kuchli_kuchsiz($profil);
    if ($kk['kuchli']) {
        echo html_writer::div(
            ui::chip('good', get_string('oq_strong', 'local_targetzenit')) . ' '
            . s($kk['kuchli']->nom) . ' '
            . html_writer::tag('span', '+' . $kk['kuchli']->delta, ['class' => 'tz-dir-t-pos'])
            . '   '
            . ui::chip('warning', get_string('oq_weak', 'local_targetzenit')) . ' '
            . s($kk['kuchsiz']->nom) . ' '
            . html_writer::tag('span', (string) $kk['kuchsiz']->delta,
                ['class' => $kk['kuchsiz']->delta >= 0 ? 'tz-dir-t-pos' : 'tz-dir-t-neg']),
            'mb-3');
    }

    echo html_writer::div(
        html_writer::tag('span', get_string('oq_below', 'local_targetzenit'), ['class' => 'tz-oq-lo'])
        . html_writer::tag('span', get_string('oq_normzero', 'local_targetzenit'),
            ['class' => 'tz-oq-mid'])
        . html_writer::tag('span', get_string('oq_above', 'local_targetzenit'),
            ['class' => 'tz-oq-hi']),
        'tz-oq-scale');

    $joriy = '';
    echo html_writer::start_div('tz-oq-prof');
    foreach ($profil as $p) {
        if ($p->guruh !== $joriy) {
            echo html_writer::div(s($p->guruh), 'tz-cat-guruh');
            $joriy = $p->guruh;
        }
        $chap = html_writer::div(s($p->nom), 'tz-oq-pname')
            . html_writer::div(
                $p->avg === null
                    ? get_string('oq_notmeasured', 'local_targetzenit')
                    : $p->avg . '% · ' . get_string('oq_normn', 'local_targetzenit',
                        (object) ['norma' => $p->norma, 'n' => $p->n]),
                'tz-dir-sub');
        $ong = $p->delta === null ? '—'
            : (($p->delta >= 0 ? '+' : '') . $p->delta);
        echo html_writer::div(
            html_writer::div($chap, 'tz-oq-pleft')
            . ui::diverge($p->delta)
            . html_writer::div(s($ong), 'tz-oq-pdelta tz-dir-t-'
                . ($p->delta === null ? 'none' : ($p->delta >= 0 ? 'pos' : 'neg'))),
            'tz-oq-prow' . ($p->kam ? ' tz-oq-fade' : ''));
    }
    echo html_writer::end_div();

    echo html_writer::div(get_string('oq_profile_note', 'local_targetzenit',
        teacher::KAM_NAMUNA), 'tz-dir-sub mt-2');
}
echo html_writer::end_div();

// ====================================================== DARSLAR RO'YXATI
$list = teacher::lessons($userid, $davr, $filter, $page, teacher::QATOR,
    ['groupkey' => $groupkey, 'subjectkey' => $subjectkey]);

echo html_writer::start_div('tz-dir-card');
echo html_writer::start_div('tz-dir-head');
echo html_writer::div(
    html_writer::tag('h4', get_string('oq_lessons', 'local_targetzenit'),
        ['class' => 'tz-dir-cardtitle'])
    . html_writer::div(get_string('dir_showing', 'local_targetzenit',
        (object) ['n' => count($list['rows']), 'jami' => $list['jami']]), 'tz-dir-sub'));

echo html_writer::start_div('tz-dir-tools');
foreach ([
    'all' => 'dir_fall',
    'nuqson' => 'oq_fgap',
    'yozuvsiz' => 'oq_fnorec',
    'tahlilli' => 'oq_fanalysed',
    'kelgusi' => 'dir_fsoon',
] as $k => $str) {
    echo html_writer::link($url(['filter' => $k, 'page' => 0]),
        html_writer::tag('b', (string) ($sanoq[$k] ?? 0)) . ' '
        . s(get_string($str, 'local_targetzenit')),
        ['class' => 'tz-dir-tab' . ($filter === $k ? ' tz-on' : '')]);
}
echo html_writer::end_div();
echo html_writer::end_div();

if (!$list['rows']) {
    echo html_writer::div(get_string('dir_nolessons', 'local_targetzenit'), 'tz-dir-sub');
} else {
    // Kurs konteksti qator-qator tekshiriladi, lekin KESHLANADI: bir sahifada
    // bitta kurs 10 martagacha uchraydi.
    $teachkesh = [];
    $teachable = function(?int $courseid) use (&$teachkesh): bool {
        if (!$courseid) {
            return false;
        }
        if (!array_key_exists($courseid, $teachkesh)) {
            $ctx = context_course::instance($courseid, IGNORE_MISSING);
            $teachkesh[$courseid] = $ctx
                && has_capability('local/targetzenit:teach', $ctx);
        }
        return $teachkesh[$courseid];
    };
    $koranaliz = has_capability('local/targetzenit:viewanalysis', $syscontext);

    echo html_writer::start_div('tz-dir-tablewrap');
    echo html_writer::start_tag('table', ['class' => 'tz-dir-table tz-oq-table']);
    echo html_writer::start_tag('thead') . html_writer::start_tag('tr');
    foreach (['oq_coldate', 'oq_colgroup', 'oq_coltopic', 'oq_colchecks',
              'dir_colscore'] as $k) {
        echo html_writer::tag('th', get_string($k, 'local_targetzenit'));
    }
    echo html_writer::tag('th', '');
    echo html_writer::end_tag('tr') . html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($list['rows'] as $r) {
        echo html_writer::start_tag('tr');

        // --- Sana va dars raqami
        echo html_writer::tag('td',
            html_writer::tag('b', userdate(strtotime($r->lessondate), '%d.%m'))
            . html_writer::div(
                s(trim(($r->starttime ?? '') . ' · ' . $r->lessonno . '-dars', ' ·')),
                'tz-dir-sub'));

        // --- Guruh va fan
        echo html_writer::tag('td',
            html_writer::tag('b', s((string) $r->groupname))
            . html_writer::div(s((string) $r->subjectname), 'tz-dir-sub')
            . ((int) $r->substituted
                ? html_writer::div(ui::chip('warning',
                    get_string('oq_subst', 'local_targetzenit')), 'mt-1')
                : ''));

        // --- Mavzu va uy vazifa (matnning o'zi — chip emas)
        $mavzu = trim((string) $r->topicshort);
        $vazifa = trim((string) $r->hwshort);
        echo html_writer::tag('td',
            ($mavzu !== ''
                ? s($mavzu)
                : html_writer::tag('span', get_string('dir_notopic', 'local_targetzenit'),
                    ['class' => 'tz-dir-sub']))
            . ($vazifa !== ''
                ? html_writer::div('🏠 ' . s($vazifa), 'tz-dir-sub')
                : ''));

        // --- "Hammasi joyidami" — majburiy uchlik + baho alohida
        $chips = ui::chip($r->hastopic ? 'good' : 'critical',
            get_string('oq_c_topic', 'local_targetzenit'));
        $chips .= ui::chip($r->attendancecount > 0 ? 'good' : 'critical',
            get_string('oq_c_att', 'local_targetzenit'),
            $r->attendancecount > 0
                ? get_string('oq_c_att_t', 'local_targetzenit', (object) [
                    'kelgan' => (int) $r->attendancecount - (int) $r->absentcount,
                    'jami' => (int) $r->attendancecount,
                    'yoq' => (int) $r->absentcount,
                    'kech' => (int) $r->latecount])
                : '');
        $chips .= ui::chip($r->yozuvbor ? 'good' : ($r->otgan ? 'critical' : 'none'),
            get_string('oq_c_rec', 'local_targetzenit'),
            $r->audioseconds
                ? gmdate($r->audioseconds >= 3600 ? 'H:i:s' : 'i:s', (int) $r->audioseconds)
                : '');
        $chips .= ui::chip($r->hashomework ? 'good' : 'none',
            get_string('oq_c_hw', 'local_targetzenit'));
        $chips .= ui::chip($r->gradecount > 0 ? 'good' : 'none',
            get_string('oq_c_grade', 'local_targetzenit'),
            (int) $r->gradecount
                ? get_string('oq_c_grade_t', 'local_targetzenit', (int) $r->gradecount)
                : '');
        if (!$r->otgan) {
            $chips = ui::chip('none', get_string('dir_notyet', 'local_targetzenit'));
        }
        // Direktor fidbegi — shu yerda FAQAT belgisi turadi; suhbatning o'zi
        // va javob yozish formasi «Dars yozuvlari» lentasida va dars
        // kokpitida. Belgisiz o'qituvchi fidbek kelganini bilmay qolardi.
        if ((int) $r->feedbackcount) {
            $chips .= ui::chip('warning',
                '💬 ' . (int) $r->feedbackcount,
                get_string('fb_title', 'local_targetzenit'));
        }
        echo html_writer::tag('td', html_writer::div($chips, 'tz-oq-chips'));

        // --- AI ball va qisqa tahlil holati
        if ($r->overallscore !== null) {
            $hujayra = ui::donut((int) $r->overallscore)
                . html_writer::div(ui::chip($r->band['status'], $r->band['nom'],
                    get_string('dir_normis', 'local_targetzenit', $norma)), 'mt-1');
            if ((int) $r->lowconfcount) {
                $hujayra .= html_writer::div(
                    get_string('oq_lowconf', 'local_targetzenit', (int) $r->lowconfcount),
                    'tz-dir-sub');
            }
        } else if ($r->analysestatus === 'failed') {
            $hujayra = ui::chip('warning', get_string('dir_failed', 'local_targetzenit'));
        } else if ($r->analysestatus === 'pending') {
            $hujayra = ui::chip('none', get_string('ai_pending', 'local_targetzenit'));
        } else {
            $hujayra = ui::chip('none', get_string('dir_noanalysisshort', 'local_targetzenit'));
        }
        echo html_writer::tag('td', $hujayra);

        // --- "SHU DARSGA O'TISH" tugmasi
        //
        // Kokpit (`dars.php`) birinchi tanlov: unda baho va yo'qlama
        // TAHRIRLANADI, audio pleyer va AI tahlili ham shu yerda. Faqat-o'qish
        // kartasi (`dars_tahlil.php`) — direktor uchun, unga `teach` ataylab
        // berilmagan.
        if ($teachable($r->courseid === null ? null : (int) $r->courseid)) {
            $tugma = html_writer::link(
                new moodle_url('/local/targetzenit/dars.php', [
                    'courseid' => (int) $r->courseid,
                    'date' => $r->lessondate,
                    'lessonno' => (int) $r->lessonno,
                ]),
                $r->lessonid
                    ? get_string('oq_open', 'local_targetzenit')
                    : get_string('oq_fill', 'local_targetzenit'),
                ['class' => 'btn btn-sm btn-outline-primary']);
        } else if ($koranaliz && $r->lessonid) {
            $tugma = html_writer::link(
                new moodle_url('/local/targetzenit/dars_tahlil.php',
                    ['lessonid' => (int) $r->lessonid]),
                get_string('oq_opencard', 'local_targetzenit'),
                ['class' => 'btn btn-sm btn-outline-secondary']);
        } else {
            // Havola QO'YILMAYDI: kursga yozilmagan odam uchun u "You cannot
            // enrol yourself" sahifasiga olib boradigan boshi berk yo'l.
            $tugma = html_writer::tag('span', '—', ['class' => 'tz-dir-sub']);
        }
        echo html_writer::tag('td', $tugma);

        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody') . html_writer::end_tag('table');
    echo html_writer::end_div();

    echo $OUTPUT->paging_bar($list['jami'], $page, teacher::QATOR, $url());
}
echo html_writer::end_div();
