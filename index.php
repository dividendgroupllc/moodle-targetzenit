<?php
// Dars jadvali sahifasi.
//
// Kim nimani ko'radi:
//   o'quvchi    -> o'z sinfining haftalik jadvali + bugungi
//   o'qituvchi  -> o'z darslari + butun maktab jadvali + bugungi
// Sanalar chorak oraliqlari va bayramlar asosida dinamik hisoblanadi
// (local_targetzenit\timetable::school_day).

require(__DIR__ . '/../../config.php');

use local_targetzenit\timetable;

require_login();

$view = optional_param('view', 'week', PARAM_ALPHA);     // week | today | all
$date = optional_param('date', date('Y-m-d'), PARAM_TEXT);
$group = optional_param('group', '', PARAM_TEXT);
$dayno = optional_param('dayno', (int) date('N'), PARAM_INT);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/index.php', ['view' => $view]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('timetable', 'local_targetzenit'));
$PAGE->set_heading(get_string('timetable', 'local_targetzenit'));

$year = timetable::current_year();
if (!$year) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('timetable_empty', 'local_targetzenit'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$terms = timetable::terms($year);
$holidays = timetable::holidays($year);
$teacherkey = timetable::teacher_key_of($USER, $year);
$mygroups = timetable::groups_of((int) $USER->id, $year);
$canviewall = has_capability('local/targetzenit:viewalltimetable', $context) || $teacherkey;

// Qaysi guruh ko'rsatiladi: so'ralgani (ruxsat bo'lsa) yoki o'zinikidan birinchisi
$allgroups = timetable::all_groups($year);
if ($group && !$canviewall && !isset($mygroups[$group])) {
    $group = '';
}
// O'qituvchi kurslarga editingteacher sifatida yozilgan, ya'ni uning ham
// "guruhi" topiladi. Shuning uchun o'qituvchiga standart ko'rinish — O'Z
// jadvali; sinf jadvalini u ro'yxatdan ataylab tanlaganda ko'rsatiladi.
if (!$group && !$teacherkey) {
    $group = $mygroups ? array_key_first($mygroups) : '';
}

// --- Jadval qatorlarini olish
$isteacherview = ($teacherkey && !$group);
$rows = [];
if ($view === 'all') {
    $rows = $canviewall ? timetable::for_day($year, $dayno) : [];
} else if ($isteacherview) {
    $rows = timetable::for_teacher($year, $teacherkey);
} else if ($group) {
    $rows = timetable::for_group($year, $group);
}

$courseids = timetable::course_ids(array_map(fn($r) => $r->courseidnumber, $rows));

/** Kurs havolasi bilan fan nomi. */
$fan = function($r) use ($courseids) {
    $nom = format_string($r->subjectname);
    if (!empty($courseids[$r->courseidnumber])) {
        $url = new moodle_url('/course/view.php', ['id' => $courseids[$r->courseidnumber]]);
        return html_writer::link($url, $nom);
    }
    return $nom;
};

echo $OUTPUT->header();

// --- Yuqori menyu
$tabs = [];
$mk = function($v, $label, $extra = []) use ($view) {
    $url = new moodle_url('/local/targetzenit/index.php', ['view' => $v] + $extra);
    $cls = ($view === $v) ? 'btn btn-primary' : 'btn btn-outline-secondary';
    return html_writer::link($url, $label, ['class' => $cls . ' mr-1 mb-2']);
};
$tabs[] = $mk('week', get_string('timetable_week', 'local_targetzenit'));
$tabs[] = $mk('today', get_string('timetable_today', 'local_targetzenit'));
if ($canviewall) {
    $tabs[] = $mk('all', get_string('timetable_all', 'local_targetzenit'));
}
echo html_writer::div(implode(' ', $tabs), 'tz-tabs mb-3');

// --- Guruh tanlash (o'qituvchi/ma'mur uchun) va "mening jadvalim"
if ($view !== 'all') {
    $bar = '';
    if ($teacherkey) {
        $mineurl = new moodle_url('/local/targetzenit/index.php', ['view' => $view, 'date' => $date]);
        $bar .= html_writer::link($mineurl,
            get_string('timetable_my', 'local_targetzenit'),
            ['class' => ($isteacherview ? 'btn btn-secondary' : 'btn btn-outline-secondary') . ' mr-2 mb-2']);
    }
    if ($canviewall && $allgroups) {
        $options = $allgroups;
        $select = new single_select(
            new moodle_url('/local/targetzenit/index.php', ['view' => $view, 'date' => $date]),
            'group', $options, $group, ['' => get_string('choose')]
        );
        $select->set_label(get_string('group', 'local_targetzenit') . ': ');
        $bar .= $OUTPUT->render($select);
    }
    if ($bar) {
        echo html_writer::div($bar, 'tz-bar mb-3 d-flex align-items-center flex-wrap');
    }
}

if (!$rows && $view !== 'all') {
    echo $OUTPUT->notification(
        $teacherkey || $group
            ? get_string('timetable_empty', 'local_targetzenit')
            : "Sizning sinfingiz aniqlanmadi — hali birorta kursga yozilmagansiz.",
        'info'
    );
    echo $OUTPUT->footer();
    exit;
}

// --- Qaysi ustunda nima ko'rsatiladi: o'qituvchiga guruh, o'quvchiga o'qituvchi
$ikkinchi_qator = function($r) use ($isteacherview) {
    return $isteacherview ? format_string($r->groupname) : format_string($r->teachername);
};

if ($view === 'today') {
    // ----------------------------------------------------------- BUGUN
    $kun = timetable::school_day($date, $terms, $holidays);
    $dn = (int) date('N', strtotime($date));
    echo html_writer::tag('h4', userdate(strtotime($date), '%A, %d-%B %Y') .
        ' · ' . s($kun['reason']));

    if (!$kun['ok']) {
        echo $OUTPUT->notification(get_string('timetable_nolessons', 'local_targetzenit'), 'info');
    } else {
        $bugun = array_filter($rows, fn($r) => (int) $r->dayno === $dn);
        if (!$bugun) {
            echo $OUTPUT->notification(get_string('timetable_nolessons', 'local_targetzenit'), 'info');
        } else {
            $t = new html_table();
            $t->head = ['#', get_string('time'), get_string('subject', 'local_targetzenit'),
                $isteacherview ? get_string('group', 'local_targetzenit') : get_string('teacher', 'local_targetzenit'),
                get_string('room', 'local_targetzenit')];
            $t->attributes['class'] = 'generaltable tz-today';
            foreach ($bugun as $r) {
                $t->data[] = [
                    $r->lessonno,
                    s($r->starttime) . ($r->endtime ? ' – ' . s($r->endtime) : ''),
                    $fan($r),
                    $ikkinchi_qator($r),
                    s($r->room),
                ];
            }
            echo html_writer::table($t);
        }
    }
    $next = date('Y-m-d', strtotime($date . ' +1 day'));
    $prev = date('Y-m-d', strtotime($date . ' -1 day'));
    echo html_writer::div(
        html_writer::link(new moodle_url('/local/targetzenit/index.php',
            ['view' => 'today', 'date' => $prev, 'group' => $group]), '← ' . $prev,
            ['class' => 'btn btn-outline-secondary mr-2']) .
        html_writer::link(new moodle_url('/local/targetzenit/index.php',
            ['view' => 'today', 'date' => $next, 'group' => $group]), $next . ' →',
            ['class' => 'btn btn-outline-secondary']),
        'mt-3'
    );

} else if ($view === 'all') {
    // ----------------------------------------------------- UMUMIY (bir kun)
    $options = [];
    foreach (timetable::DAYS as $d => $nom) {
        $options[$d] = $nom;
    }
    $select = new single_select(
        new moodle_url('/local/targetzenit/index.php', ['view' => 'all']),
        'dayno', $options, $dayno
    );
    $select->set_label(get_string('day') . ': ');
    echo html_writer::div($OUTPUT->render($select), 'mb-3');

    if (!$rows) {
        echo $OUTPUT->notification(get_string('timetable_empty', 'local_targetzenit'), 'info');
    } else {
        $maxles = max(array_map(fn($r) => (int) $r->lessonno, $rows));
        $perguruh = [];
        foreach ($rows as $r) {
            $perguruh[$r->groupname][(int) $r->lessonno] = $r;
        }
        ksort($perguruh);

        $t = new html_table();
        $t->head = array_merge([get_string('group', 'local_targetzenit')],
            array_map(fn($i) => (string) $i, range(1, $maxles)));
        $t->attributes['class'] = 'generaltable tz-all';
        foreach ($perguruh as $gnom => $darslar) {
            $qator = [html_writer::tag('strong', format_string($gnom))];
            for ($i = 1; $i <= $maxles; $i++) {
                if (isset($darslar[$i])) {
                    $r = $darslar[$i];
                    $qator[] = html_writer::div(format_string($r->subjectname), 'tz-subj') .
                        html_writer::div(format_string($r->teachername), 'tz-meta') .
                        ($r->room ? html_writer::div(s($r->room), 'tz-meta') : '');
                } else {
                    $qator[] = '';
                }
            }
            $t->data[] = $qator;
        }
        echo html_writer::div(html_writer::table($t), 'tz-scroll');
    }

} else {
    // ------------------------------------------------------------ HAFTA
    $hafta = timetable::week_dates($date);
    $maxles = $rows ? max(array_map(fn($r) => (int) $r->lessonno, $rows)) : 0;

    $sarlavha = $isteacherview
        ? get_string('timetable_my', 'local_targetzenit')
        : format_string($allgroups[$group] ?? $group);
    echo html_writer::tag('h4', $sarlavha . ' · ' .
        $hafta[1] . ' – ' . $hafta[5]);

    $prev = date('Y-m-d', strtotime($hafta[1] . ' -7 days'));
    $next = date('Y-m-d', strtotime($hafta[1] . ' +7 days'));
    $navargs = ['view' => 'week', 'group' => $group];
    echo html_writer::div(
        html_writer::link(new moodle_url('/local/targetzenit/index.php',
            $navargs + ['date' => $prev]), '← oldingi hafta',
            ['class' => 'btn btn-outline-secondary mr-2']) .
        html_writer::link(new moodle_url('/local/targetzenit/index.php',
            $navargs + ['date' => date('Y-m-d')]), 'shu hafta',
            ['class' => 'btn btn-outline-secondary mr-2']) .
        html_writer::link(new moodle_url('/local/targetzenit/index.php',
            $navargs + ['date' => $next]), 'keyingi hafta →',
            ['class' => 'btn btn-outline-secondary']),
        'mb-3'
    );

    $katak = [];
    foreach ($rows as $r) {
        $katak[(int) $r->dayno][(int) $r->lessonno][] = $r;
    }

    $t = new html_table();
    $bugun = date('Y-m-d');
    $head = [get_string('lesson', 'local_targetzenit')];
    foreach (timetable::DAYS as $d => $nom) {
        $sana = $hafta[$d];
        $kun = timetable::school_day($sana, $terms, $holidays);
        $label = html_writer::div($nom, 'tz-day') .
            html_writer::div(userdate(strtotime($sana), '%d-%B'), 'tz-date');
        if (!$kun['ok']) {
            $label .= html_writer::div(s($kun['reason']), 'tz-off');
        }
        if ($sana === $bugun) {
            $label = html_writer::div($label, 'tz-istoday');
        }
        $head[] = $label;
    }
    $t->head = $head;
    $t->attributes['class'] = 'generaltable tz-week';

    for ($i = 1; $i <= $maxles; $i++) {
        $qator = [html_writer::tag('strong', (string) $i)];
        foreach (timetable::DAYS as $d => $nom) {
            $sana = $hafta[$d];
            $kun = timetable::school_day($sana, $terms, $holidays);
            if (!$kun['ok']) {
                $qator[] = html_writer::div('', 'tz-offcell');
                continue;
            }
            $ichi = '';
            foreach ($katak[$d][$i] ?? [] as $r) {
                $ichi .= html_writer::div($fan($r), 'tz-subj') .
                    html_writer::div($ikkinchi_qator($r), 'tz-meta') .
                    (($r->starttime) ? html_writer::div(s($r->starttime) .
                        ($r->endtime ? '–' . s($r->endtime) : ''), 'tz-meta') : '') .
                    ($r->room ? html_writer::div(s($r->room), 'tz-meta') : '');
            }
            $qator[] = $ichi;
        }
        $t->data[] = $qator;
    }
    echo html_writer::div(html_writer::table($t), 'tz-scroll');
}

echo $OUTPUT->footer();
