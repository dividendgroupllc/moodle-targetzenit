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
$date = optional_param('date', timetable::bugun(), PARAM_TEXT);
$group = optional_param('group', '', PARAM_TEXT);
$dayno = optional_param('dayno', timetable::bugun_dayno(), PARAM_INT);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = timetable::bugun();
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

// Kalit nomuvofiqligi — JIM YIQILISHNI ko'rinadigan qiladi. Kalit mos
// kelmasa sahifa o'z-o'zidan "sinf jadvali" rejimiga o'tadi va o'qituvchi
// buning sababini bilmaydi. Faqat ANIQLANADI; xabar header'dan keyin
// chiqariladi (undan oldin echo qilish mumkin emas).
$kalitxatosi = !$teacherkey && timetable::kalit_nomuvofiqmi($USER, $year);

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

/**
 * Fan nomi havola bilan.
 * O'qituvchi o'z jadvalini ko'rayotganda havola to'g'ridan-to'g'ri DARS
 * KOKPITIGA olib boradi (mavzu + yo'qlama + baho), kursning o'ziga emas —
 * kunlik ish aynan shu yerda bajariladi.
 */
$kirish_keshi = [];
$fan = function($r, $sana = null) use ($courseids, $isteacherview, &$kirish_keshi) {
    $nom = format_string($r->subjectname);
    $cid = $courseids[$r->courseidnumber] ?? 0;
    if (!$cid) {
        return $nom;
    }
    if ($isteacherview && $sana) {
        $url = new moodle_url('/local/targetzenit/dars.php', [
            'courseid' => $cid,
            'date' => $sana,
            'lessonno' => (int) $r->lessonno,
        ]);
        return html_writer::link($url, $nom, ['title' => get_string('openlesson', 'local_targetzenit')]);
    }

    // Kursga kirish huquqi bo'lmasa HAVOLA QO'YILMAYDI.
    //
    // NEGA: sinf jadvalida boshqa o'qituvchilarning fanlari ham turadi.
    // Ularning kursiga bu odam yozilmagan, shuning uchun havola bosilganda
    // Moodle "You cannot enrol yourself in this course" sahifasini ko'rsatadi
    // — boshi berk yo'l. Havola bo'lmasa fan nomi oddiy matn bo'lib turadi.
    if (!array_key_exists($cid, $kirish_keshi)) {
        // Bir sahifada bitta kurs bir necha marta uchraydi (haftaning turli
        // kunlarida), shuning uchun natija keshlanadi.
        $kurs = get_course($cid);
        $kirish_keshi[$cid] = $kurs && can_access_course($kurs);
    }
    if (!$kirish_keshi[$cid]) {
        return $nom;
    }
    return html_writer::link(new moodle_url('/course/view.php', ['id' => $cid]), $nom);
};

echo $OUTPUT->header();

if ($kalitxatosi) {
    echo $OUTPUT->notification(get_string('tt_keymismatch', 'local_targetzenit'),
        \core\output\notification::NOTIFY_WARNING);
}

// --- Yuqori menyu
$tabs = [];
$mk = function($v, $label, $extra = []) use ($view) {
    $url = new moodle_url('/local/targetzenit/index.php', ['view' => $v] + $extra);
    $cls = ($view === $v) ? 'btn btn-primary' : 'btn btn-outline-secondary';
    return html_writer::link($url, $label, ['class' => $cls . ' mr-1 mb-2']);
};
$tabs[] = $mk('week', get_string('timetable_week', 'local_targetzenit'));
// Yorliq nomi «Lessons» — boshqa sahifalardagi tugmalar ham shu nom bilan
// shu yerga olib keladi. Bir manzilning ikki nomi bo'lishi chalg'itadi,
// shuning uchun «Today» emas: sana tanlagich baribir pastda turibdi.
$tabs[] = $mk('today', get_string('lessons_link', 'local_targetzenit'));
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
            : get_string('tt_noclass', 'local_targetzenit'),
        'info'
    );
    echo $OUTPUT->footer();
    exit;
}

// --- Qaysi ustunda nima ko'rsatiladi: o'qituvchiga guruh, o'quvchiga o'qituvchi
// Sinf jadvalida blok darslari ham ko'rinadi — `for_group()` ularni
// `memberclasses` orqali topadi. Bunday darsda guruh SINF emas, blok
// (masalan «ENGLISH High 8-9»), ya'ni bitta katakda bitta fan bir necha
// marta — har darajaga bittadan — turishi mumkin. Shuning uchun guruh nomi
// ham yoziladi: aks holda o'quvchi qaysi qator o'ziga tegishli ekanini
// bilmaydi.
$ikkinchi_qator = function($r) use ($isteacherview, $group) {
    if ($isteacherview) {
        return format_string($r->groupname);
    }
    $oqituvchi = format_string($r->teachername);
    if ($group !== '' && $r->groupkey !== $group) {
        return format_string($r->groupname) . ' · ' . $oqituvchi;
    }
    return $oqituvchi;
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
            // O'qituvchiga amal ustuni: yozib olish va darsga kirish.
            //
            // NEGA SHU YERDA: bu sahifa o'qituvchining kundalik boshlanish
            // nuqtasi — jadvaldan "bugun" ga kiradi va shu yerdan darsni
            // boshlaydi. Avval har dars uchun fan nomini bosib kirish,
            // keyin ichkaridan yozuvni ochish kerak edi: ikki bosqich,
            // dars boshlanayotgan paytda esa har bosqich qimmat.
            $amalbor = $isteacherview;
            $holat = [];
            if ($amalbor) {
                $cids = [];
                foreach ($bugun as $r) {
                    $cid = $courseids[$r->courseidnumber] ?? 0;
                    if ($cid) {
                        $cids[] = $cid;
                    }
                }
                $holat = \local_targetzenit\teacher::day_state($date, $cids);
            }

            $t = new html_table();
            $t->head = ['#', get_string('time'), get_string('subject', 'local_targetzenit'),
                $isteacherview ? get_string('group', 'local_targetzenit') : get_string('teacher', 'local_targetzenit'),
                get_string('room', 'local_targetzenit')];
            if ($amalbor) {
                $t->head[] = get_string('tt_colaction', 'local_targetzenit');
            }
            $t->attributes['class'] = 'generaltable tz-today';
            foreach ($bugun as $r) {
                $qator = [
                    $r->lessonno,
                    s($r->starttime) . ($r->endtime ? ' – ' . s($r->endtime) : ''),
                    $fan($r, $date),
                    $ikkinchi_qator($r),
                    s($r->room),
                ];

                if ($amalbor) {
                    $cid = $courseids[$r->courseidnumber] ?? 0;
                    if (!$cid) {
                        // Kurs Moodle'da yo'q — havola qo'yilsa boshi berk yo'l
                        $qator[] = html_writer::tag('span', '—', ['class' => 'text-muted']);
                    } else {
                        $h = $holat[$cid . ':' . (int) $r->lessonno] ?? null;
                        $p = ['courseid' => $cid, 'date' => $date,
                              'lessonno' => (int) $r->lessonno];

                        // Yozuv tugmasi TO'G'RIDAN-TO'G'RI yozuv oynasiga.
                        // Jurnal yozuvi (`_lesson`) kerak bo'lsa `rec.php`
                        // o'zi yaratadi.
                        //
                        // Havola ODDIY havola bo'lib qoladi: JS pastda uni
                        // kichik oynada ochadi, lekin JS ishlamasa ham tugma
                        // ishlaydi. Oyna foydalanuvchi BOSGANIDA ochiladi,
                        // ya'ni brauzer uni popup deb to'smaydi.
                        $yozilgan = $h && $h->yozuvbor;
                        $amal = html_writer::link(
                            new moodle_url('/local/targetzenit/rec.php', $p),
                            ($yozilgan ? '✓ ' : '🎙 ')
                            . get_string($yozilgan ? 'tt_recorded' : 'tt_record',
                                'local_targetzenit'),
                            ['class' => 'btn btn-sm ' . ($yozilgan
                                ? 'btn-outline-success' : 'btn-danger') . ' mr-1 tz-recbtn',
                             'target' => '_blank', 'rel' => 'noopener']);

                        $amal .= html_writer::link(
                            new moodle_url('/local/targetzenit/dars.php', $p),
                            get_string('oq_open', 'local_targetzenit'),
                            ['class' => 'btn btn-sm btn-outline-secondary']);

                        // Nima yetishmayotgani — qisqa belgi
                        $yetmas = [];
                        if (!$h || trim((string) $h->topic) === '') {
                            $yetmas[] = get_string('oq_c_topic', 'local_targetzenit');
                        }
                        if (!$h || !(int) $h->yozuvlar) {
                            $yetmas[] = get_string('oq_c_att', 'local_targetzenit');
                        }
                        if (!$yozilgan) {
                            $yetmas[] = get_string('oq_c_rec', 'local_targetzenit');
                        }
                        if ($yetmas) {
                            $amal .= html_writer::div(
                                get_string('tt_missing', 'local_targetzenit',
                                    implode(', ', $yetmas)),
                                'text-muted small mt-1');
                        }
                        $qator[] = $amal;
                    }
                }
                $t->data[] = $qator;
            }
            echo html_writer::table($t);
        }
    }
    // Yozuv havolalarini kichik oynada ochish (dars sahifasidagi bilan bir xil
    // o'lcham). Oyna foydalanuvchi bosganda ochilgani uchun to'silmaydi;
    // `window.open` muvaffaqiyatsiz bo'lsa havola odatdagidek ochiladi.
    $PAGE->requires->js_amd_inline("
        require([], function() {
            document.querySelectorAll('.tz-recbtn').forEach(function (a) {
                a.addEventListener('click', function (e) {
                    var w = window.open(a.href, 'tzrec',
                        'width=420,height=460,menubar=no,toolbar=no,location=no');
                    if (w) { e.preventDefault(); w.focus(); }
                });
            });
        });
    ");

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
    foreach (array_keys(timetable::DAYS) as $d) {
        $options[$d] = timetable::kun_nomi($d);
    }
    $select = new single_select(
        new moodle_url('/local/targetzenit/index.php', ['view' => 'all']),
        'dayno', $options, $dayno
    );
    $select->set_label(get_string('day') . ': ');

    // Kun tanlash + qidiruv + o'lcham — bitta qatorda
    echo html_writer::start_div('d-flex flex-wrap align-items-center mb-3', ['style' => 'gap:16px']);
    echo $OUTPUT->render($select);
    echo html_writer::empty_tag('input', [
        'type' => 'search', 'id' => 'tz-all-filter', 'class' => 'form-control form-control-sm tz-filter',
        'placeholder' => get_string('tt_search', 'local_targetzenit'),
        'autocomplete' => 'off',
    ]);
    echo local_targetzenit_zoom_panel('umumiy');
    echo html_writer::end_div();

    // Qidiruv natijasi sanog'i JS'da yoziladi, matn esa tarjima qilinadi.
    // Satrda {$a} o'rni bor, shuning uchun JS tomonga __N__ belgisi bilan
    // beriladi va o'sha joyga son qo'yiladi.
    $hisobmatn = json_encode(
        get_string('tt_classcount', 'local_targetzenit', '__N__'),
        JSON_UNESCAPED_UNICODE);

    // Qidiruv panjarada QATORNI YASHIRMAYDI: qator — dars raqami, uni
    // yashirsak jadval shakli buziladi. O'qituvchi/fan izlanganda mos
    // kelmagan darslar xiralashadi, panjara joyida qoladi.
    $PAGE->requires->js_amd_inline("
        require([], function() {
            var inp = document.getElementById('tz-all-filter');
            if (!inp) { return; }
            var hisob = document.getElementById('tz-all-hisob');
            var asl = hisob ? hisob.textContent : '';
            inp.addEventListener('input', function() {
                var q = inp.value.trim().toLowerCase();
                var darslar = document.querySelectorAll('.tz-grid .tz-lesson');
                var topildi = 0;
                Array.prototype.forEach.call(darslar, function(el) {
                    var mos = !q || el.textContent.toLowerCase().indexOf(q) !== -1;
                    el.classList.toggle('tz-dim', !mos);
                    if (q && mos) { topildi++; }
                });
                if (hisob) {
                    hisob.textContent = q
                        ? {$hisobmatn}.replace('__N__', topildi)
                        : asl;
                }
            });
        });
    ");

    // PANJARA: ustunlar = SINFLAR, qatorlar = darslar.
    //
    // NEGA SHUNDAY: avval qatorlar GURUH edi, ya'ni ro'yxatda «ENGLISH
    // 8A+8B+9A+9B (qo'shma)» kabi 40 ta blok nomi turardi va bir sinfning
    // kuni qaysi qatorlarda ekanini topib bo'lmasdi. Zavuch esa kunni SINF
    // kesimida o'qiydi. Shuning uchun Frappe'dagi («Dars jadvali» paneli)
    // bilan bir xil shaklga keltirildi: bitta kun, 20 sinf ustun, 10 dars
    // qator. Blok darsi o'ziga tegishli HAR BIR sinf ustunida ko'rinadi —
    // buni `memberclasses` beradi.
    $sinflar = timetable::sinflar($year);
    $panjara = timetable::panjara($year, $dayno);
    $bloklar = timetable::blok_groupkeys($year);

    if (!$sinflar || !$panjara) {
        echo $OUTPUT->notification(get_string('timetable_empty', 'local_targetzenit'), 'info');
    } else {
        $maxles = 0;
        foreach (array_keys($panjara) as $k) {
            $maxles = max($maxles, (int) substr($k, strrpos($k, '|') + 1));
        }

        // Fan nomidan barqaror rang: bir fan kun bo'yi bir xil rangda
        // ko'rinadi, ya'ni ko'z panjarani rang bo'yicha o'qiydi.
        $hue = function (string $fan): int {
            $h = 0;
            foreach (preg_split('//u', $fan, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
                $h = ($h * 31 + mb_ord($ch)) % 360;
            }
            return $h;
        };

        $bosh = html_writer::tag('th', '№', ['class' => 'tz-no']);
        foreach ($sinflar as $s) {
            $qisqa = preg_replace('/\s*\((full|hybrid)\)\s*/i', '', $s);
            $tur = stripos($s, 'hybrid') !== false ? 'hybrid' : 'full';
            $bosh .= html_writer::tag('th',
                s($qisqa) . html_writer::tag('span', $tur));
        }

        $tana = '';
        for ($i = 1; $i <= $maxles; $i++) {
            // Vaqt: shu darsdagi birinchi topilgan yozuvdan (qo'ng'iroq
            // jadvali sinfga qarab farq qilsa ham, ustun bitta)
            $vaqt = '';
            foreach ($sinflar as $s) {
                foreach ($panjara[$s . '|' . $i] ?? [] as $r) {
                    if ($r->starttime) {
                        $vaqt = $r->starttime . ($r->endtime ? '–' . $r->endtime : '');
                        break 2;
                    }
                }
            }
            $qator = html_writer::tag('th',
                html_writer::tag('b', (string) $i) . html_writer::tag('span', s($vaqt)),
                ['class' => 'tz-no']);

            foreach ($sinflar as $s) {
                $list = $panjara[$s . '|' . $i] ?? [];
                if (!$list) {
                    $qator .= html_writer::tag('td', '', ['class' => 'tz-bosh']);
                    continue;
                }
                $ichi = '';
                foreach ($list as $r) {
                    $h = $hue((string) $r->subjectkey);
                    // Blok darsida DARAJA so'zi fan yoniga qo'shiladi
                    // («ENGLISH High»), ostida esa o'qituvchi. Bitta katakda
                    // bir fan bir necha daraja bilan turishi mumkin va qaysi
                    // qator kimga tegishli ekani faqat shundan bilinadi.
                    //
                    // FAQAT daraja so'zi olinadi, to'liq guruh nomi emas:
                    // «ENGLISH High 8-9» -> «High». Sinf diapazoni ustun
                    // sarlavhasidan allaqachon ma'lum, uni takrorlash katakni
                    // to'ldirib yuboradi. To'liq nom `title` da qoladi.
                    // Frappe'dagi panel ham aynan shunday ko'rsatadi.
                    $tag = '';
                    if (isset($bloklar[$r->groupkey])) {
                        $d = local_targetzenit_daraja_sozi(
                            (string) $r->groupname, (string) $r->subjectkey);
                        if ($d !== '') {
                            $tag = ' ' . html_writer::tag('span', s($d), ['class' => 'tz-bk']);
                        }
                    }
                    $ichi .= html_writer::div(
                        html_writer::div(format_string($r->subjectname) . $tag, 'tz-g-fan') .
                        html_writer::div(format_string($r->teachername ?: '—'), 'tz-g-oqit') .
                        ($r->room ? html_writer::div(s($r->room), 'tz-g-oqit') : ''),
                        'tz-lesson', [
                            'style' => '--h:' . $h,
                            'title' => format_string($r->subjectname) . ' · '
                                . format_string($r->groupname) . ' · '
                                . format_string($r->teachername),
                        ]);
                }
                $qator .= html_writer::tag('td', $ichi,
                    ['class' => count($list) > 1 ? 'tz-multi' : '']);
            }
            $tana .= html_writer::tag('tr', $qator);
        }

        echo html_writer::div(
            html_writer::tag('span',
                get_string('tt_classcount', 'local_targetzenit', count($sinflar)),
                ['id' => 'tz-all-hisob']),
            'text-muted small mb-1');
        echo html_writer::div(
            html_writer::tag('table',
                html_writer::tag('thead', html_writer::tag('tr', $bosh)) .
                html_writer::tag('tbody', $tana),
                ['class' => 'tz-grid']),
            'tz-all-scroll tz-grid-wrap');
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
            $navargs + ['date' => $prev]), get_string('tt_prevweek', 'local_targetzenit'),
            ['class' => 'btn btn-outline-secondary mr-2']) .
        html_writer::link(new moodle_url('/local/targetzenit/index.php',
            $navargs + ['date' => timetable::bugun()]), get_string('tt_thisweek', 'local_targetzenit'),
            ['class' => 'btn btn-outline-secondary mr-2']) .
        html_writer::link(new moodle_url('/local/targetzenit/index.php',
            $navargs + ['date' => $next]), get_string('tt_nextweek', 'local_targetzenit'),
            ['class' => 'btn btn-outline-secondary']),
        'mb-3'
    );

    $katak = [];
    foreach ($rows as $r) {
        $katak[(int) $r->dayno][(int) $r->lessonno][] = $r;
    }

    $t = new html_table();
    $bugun = timetable::bugun();
    $head = [get_string('lesson', 'local_targetzenit')];
    foreach (array_keys(timetable::DAYS) as $d) {
        $nom = timetable::kun_nomi($d);
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
        foreach (array_keys(timetable::DAYS) as $d) {
            $sana = $hafta[$d];
            $kun = timetable::school_day($sana, $terms, $holidays);
            if (!$kun['ok']) {
                $qator[] = html_writer::div('', 'tz-offcell');
                continue;
            }
            $ichi = '';
            foreach ($katak[$d][$i] ?? [] as $r) {
                $ichi .= html_writer::div($fan($r, $sana), 'tz-subj') .
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
