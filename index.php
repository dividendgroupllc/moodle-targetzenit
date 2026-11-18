<?php
// Dars jadvali sahifasi.
//
// Kim nimani ko'radi:
//   o'quvchi    -> o'z sinfining haftalik jadvali + bugungi
//   o'qituvchi  -> o'z darslari + butun maktab jadvali + bugungi
// Sanalar chorak oraliqlari va bayramlar asosida dinamik hisoblanadi
// (local_targetzenit\timetable::school_day).

require(__DIR__ . '/../../config.php');
// lib.php funksiyalari (daraja_sozi, pluginfile...) — embedded/qobiq
// rejimida Moodle uni avtomatik yuklamaydi, shuning uchun aniq yuklaymiz.
require_once(__DIR__ . '/lib.php');

use local_targetzenit\timetable;

require_login();

$view = optional_param('view', 'week', PARAM_ALPHA);     // week | today | all
$date = optional_param('date', timetable::bugun(), PARAM_TEXT);
$group = optional_param('group', '', PARAM_TEXT);
// Butun maktab ko'rinishi endi SANA bilan yuradi (maket: ‹ sana › + kalendar,
// standart — bugun); eski dayno parametri moslik uchun qoladi.
$dayno = optional_param('dayno', 0, PARAM_INT);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = timetable::bugun();
}
if ($dayno < 1 || $dayno > 7) {
    $dayno = (int) date('N', strtotime($date));
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

// O'QITUVCHIGA — to'liq maket qobig'i (Moodle UI ko'rinmaydi); o'quvchi va
// boshqa rollarga sahifa odatdagi Moodle ko'rinishida qoladi.
$qobiq = $teacherkey !== '' || \local_targetzenit\teacher::has_any((int) $USER->id);
if ($qobiq) {
    $PAGE->set_pagelayout('embedded');
    $PAGE->requires->css('/local/targetzenit/styles.css');
}
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
// «Mening haftam» (week) — DOIM o'qituvchining shaxsiy jadvali: guruh
// tanlangan bo'lsa ham o'z darslari ko'rinadi (2026-11-18 talabi). Butun
// maktab/guruh jadvali «Butun maktab» (all) ko'rinishida.
if ($view === 'week' && $teacherkey) {
    $group = '';
}
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

if ($qobiq) {
    \local_targetzenit\output\maket::boshla('jadval');
    echo html_writer::start_div('tz-ishstoli-wrap');
} else {
    echo $OUTPUT->header();
}

if ($kalitxatosi) {
    echo $OUTPUT->notification(get_string('tt_keymismatch', 'local_targetzenit'),
        \core\output\notification::NOTIFY_WARNING);
}

// --- Ko'rinish almashtirgich (maket: yaqqol segmentli tanlov).
// «Butun maktab» o'qituvchiga ham ko'rinadi ($canviewall teacherkey bilan ham
// true bo'ladi) — avval ichki tab noaniq edi, endi segment yaqqol.
$mk = function($v, $label, $extra = []) use ($view) {
    $url = new moodle_url('/local/targetzenit/index.php', ['view' => $v] + $extra);
    return html_writer::link($url, $label,
        ['class' => 'tz-vtab' . ($view === $v ? ' tz-on' : '')]);
};
$tabs = [
    $mk('week', get_string('timetable_week', 'local_targetzenit')),
    $mk('today', get_string('lessons_link', 'local_targetzenit')),
];
if ($canviewall) {
    $tabs[] = $mk('all', get_string('timetable_all', 'local_targetzenit'));
}
echo html_writer::div(implode('', $tabs), 'tz-vtabs mb-3');

// --- Guruh tanlash — faqat «Bugun» (today) ko'rinishida (week doim shaxsiy).
if ($view === 'today') {
    $bar = '';
    if ($teacherkey) {
        $mineurl = new moodle_url('/local/targetzenit/index.php', ['view' => $view, 'date' => $date]);
        $bar .= html_writer::link($mineurl,
            get_string('timetable_my', 'local_targetzenit'),
            ['class' => ($isteacherview ? 'btn btn-secondary' : 'btn btn-outline-secondary') . ' me-2 mb-2']);
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
    if ($qobiq) {
        echo html_writer::end_div();
        \local_targetzenit\output\maket::tugat();
    } else {
        echo $OUTPUT->footer();
    }
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
                                ? 'btn-outline-success' : 'btn-danger') . ' me-1 tz-recbtn',
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
            ['class' => 'btn btn-outline-secondary me-2']) .
        html_writer::link(new moodle_url('/local/targetzenit/index.php',
            ['view' => 'today', 'date' => $next, 'group' => $group]), $next . ' →',
            ['class' => 'btn btn-outline-secondary']),
        'mt-3'
    );

} else if ($view === 'all') {
    // ----------------------------------------------------- UMUMIY (bir kun)
    $options = [];
    // SANA PANELI (maket): ‹ › + kalendar + «Bugun». Panjara haftalik
    // shablondan chiziladi (dayno), sana esa qaysi kun ekanini aniqlaydi va
    // dam olish/ta'til bo'lsa sababi ko'rsatiladi.
    $oldin = date('Y-m-d', strtotime($date . ' -1 day'));
    $keyin = date('Y-m-d', strtotime($date . ' +1 day'));
    echo html_writer::start_div('tzp-datebar');
    echo html_writer::link(new moodle_url('/local/targetzenit/index.php',
        ['view' => 'all', 'date' => $oldin]), '‹', ['class' => 'tzp-btn']);
    echo html_writer::tag('b',
        userdate(strtotime($date), '%A, %d-%B')
        . ($date === timetable::bugun()
            ? ' (' . get_string('bosh_today', 'local_targetzenit') . ')' : ''),
        ['class' => 'tzp-dlbl']);
    echo html_writer::link(new moodle_url('/local/targetzenit/index.php',
        ['view' => 'all', 'date' => $keyin]), '›', ['class' => 'tzp-btn']);
    echo html_writer::start_tag('form', ['method' => 'get',
        'action' => new moodle_url('/local/targetzenit/index.php'),
        'class' => 'd-inline m-0']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view',
        'value' => 'all']);
    echo html_writer::empty_tag('input', ['type' => 'date', 'name' => 'date',
        'value' => $date, 'class' => 'form-control d-inline-block',
        'style' => 'width:auto', 'onchange' => 'this.form.submit()']);
    echo html_writer::end_tag('form');
    if ($date !== timetable::bugun()) {
        echo html_writer::link(new moodle_url('/local/targetzenit/index.php',
            ['view' => 'all']), get_string('bosh_today', 'local_targetzenit'),
            ['class' => 'tzp-btn tzp-btn-pri']);
    }
    echo html_writer::end_div();

    $kunhodisa = timetable::school_day($date, $terms, $holidays);
    if (!$kunhodisa['ok']) {
        echo html_writer::div(s($kunhodisa['reason']), 'tzp-dnote');
    }

    // Qidiruv (o'qituvchi/fan bo'yicha xiralashtirish)
    echo html_writer::start_div('d-flex flex-wrap align-items-center mb-3', ['style' => 'gap:16px']);
    echo html_writer::empty_tag('input', [
        'type' => 'search', 'id' => 'tz-all-filter', 'class' => 'form-control form-control-sm tz-filter',
        'placeholder' => get_string('tt_search', 'local_targetzenit'),
        'autocomplete' => 'off',
    ]);
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
                    // O'z darsi — brend ramka bilan ajratiladi va BOSILADI
                    // (kokpitga olib boradi). Begona darsga kirish YO'Q:
                    // o'qituvchi o'ziga biriktirilmagan darsni faqat ko'radi
                    // (2026-11-18 talabi) — div link emas.
                    $meniki = $teacherkey !== ''
                        && (string) $r->teacherkey === $teacherkey;
                    $cid = $courseids[$r->courseidnumber] ?? 0;
                    $tark =
                        html_writer::div(format_string($r->subjectname) . $tag, 'tz-g-fan') .
                        html_writer::div(format_string($r->teachername ?: '—'), 'tz-g-oqit') .
                        ($r->room ? html_writer::div(s($r->room), 'tz-g-oqit') : '');
                    $atr = ['style' => '--h:' . $h,
                        'title' => format_string($r->subjectname) . ' · '
                            . format_string($r->groupname) . ' · '
                            . format_string($r->teachername)];
                    if ($meniki && $cid) {
                        $atr['class'] = 'tz-lesson tz-mine';
                        $atr['href'] = (new moodle_url('/local/targetzenit/dars.php', [
                            'courseid' => $cid, 'date' => $date,
                            'lessonno' => $i]))->out(false);
                        $ichi .= html_writer::tag('a', $tark, $atr);
                    } else {
                        $atr['class'] = 'tz-lesson';
                        $ichi .= html_writer::div($tark, '', $atr);
                    }
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

    // MAKET sana paneli: ‹ | sarlavha · oralig'i (joriy hafta) | › + «Joriy hafta»
    $sarlavha = $isteacherview
        ? get_string('timetable_my', 'local_targetzenit')
        : format_string($allgroups[$group] ?? $group);
    $prev = date('Y-m-d', strtotime($hafta[1] . ' -7 days'));
    $next = date('Y-m-d', strtotime($hafta[1] . ' +7 days'));
    $navargs = ['view' => 'week', 'group' => $group];
    $bugun = timetable::bugun();
    $joriyhafta = ($bugun >= $hafta[1] && $bugun <= end($hafta));

    echo html_writer::start_div('tzp-datebar');
    echo html_writer::link(new moodle_url('/local/targetzenit/index.php',
        $navargs + ['date' => $prev]), '‹', ['class' => 'tzp-btn']);
    echo html_writer::tag('b',
        $sarlavha . ' · '
        . userdate(strtotime($hafta[1]), '%d-%B') . ' – '
        . userdate(strtotime(end($hafta)), '%d-%B')
        . ($joriyhafta ? ' (' . get_string('tt_thisweek', 'local_targetzenit') . ')' : ''),
        ['class' => 'tzp-dlbl']);
    echo html_writer::link(new moodle_url('/local/targetzenit/index.php',
        $navargs + ['date' => $next]), '›', ['class' => 'tzp-btn']);
    if (!$joriyhafta) {
        echo html_writer::link(new moodle_url('/local/targetzenit/index.php',
            $navargs + ['date' => $bugun]),
            get_string('tt_thisweek', 'local_targetzenit'),
            ['class' => 'tzp-btn tzp-btn-pri']);
    }
    echo html_writer::end_div();

    $katak = [];
    $paravaqt = [];
    foreach ($rows as $r) {
        $katak[(int) $r->dayno][(int) $r->lessonno][] = $r;
        // Para vaqti — qator sarlavhasida ko'rsatiladi (maket: «1 / 08:30»)
        if ($r->starttime && empty($paravaqt[(int) $r->lessonno])) {
            $paravaqt[(int) $r->lessonno] = (string) $r->starttime;
        }
    }

    // KTP MAVZULARI (2026-11-18 talabi): o'z jadvalida har darsning rejadagi
    // mavzusi ko'rinadi. Bitta so'rov — haftadagi barcha slotlarning mavzusi,
    // kalit (sana#para) bo'yicha. Dars o'tilgan bo'lsa FAKT mavzu ustun.
    $mavzuhafta = [];
    if ($hafta) {
        $idn = array_values(array_unique(array_filter(array_map(
            fn($r) => $r->courseidnumber, $rows))));
        if ($idn) {
            [$in, $prm] = $DB->get_in_or_equal(
                array_map(fn($r) => $courseids[$r] ?? 0, $idn), SQL_PARAMS_NAMED);
            $prm['a'] = $hafta[1];
            $prm['b'] = end($hafta);
            // Reja (slot) mavzulari
            $srows = $DB->get_records_sql("
                SELECT s.id, s.lessondate, s.lessonno, s.mavzu
                  FROM {local_targetzenit_slot} s
                 WHERE s.courseid $in AND s.mavzuid > 0
                   AND s.lessondate >= :a AND s.lessondate <= :b", $prm);
            foreach ($srows as $sr) {
                $mavzuhafta[$sr->lessondate . '#' . (int) $sr->lessonno]
                    = ['reja', trim((string) $sr->mavzu)];
            }
            // Fakt (o'tilgan dars) mavzulari — reja ustiga yoziladi
            $lrows = $DB->get_records_sql("
                SELECT l.id, l.lessondate, l.lessonno, l.topic
                  FROM {local_targetzenit_lesson} l
                 WHERE l.courseid $in AND l.topic IS NOT NULL AND l.topic <> ''
                   AND l.lessondate >= :a AND l.lessondate <= :b", $prm);
            foreach ($lrows as $lr) {
                $mavzuhafta[$lr->lessondate . '#' . (int) $lr->lessonno]
                    = ['fakt', trim((string) $lr->topic)];
            }
        }
    }

    // Fan nomidan barqaror rang (butun-maktab panjarasi bilan bir xil
    // funksiya) — bir fan hafta bo'yi bir xil pastel rangda ko'rinadi.
    $hue = function (string $fan): int {
        $hh = 0;
        foreach (preg_split('//u', $fan, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
            $hh = ($hh * 31 + mb_ord($ch)) % 360;
        }
        return $hh;
    };

    $t = new html_table();
    $bugun = timetable::bugun();
    $head = [get_string('lesson', 'local_targetzenit')];
    foreach (array_keys(timetable::DAYS) as $d) {
        $nom = timetable::kun_nomi($d);
        $sana = $hafta[$d];
        $kun = timetable::school_day($sana, $terms, $holidays);
        $label = html_writer::tag('span', $nom, ['class' => 'tz-dname'])
            . html_writer::tag('span', userdate(strtotime($sana), '%d-%B'),
                ['class' => 'tz-ddate']);
        if (!$kun['ok']) {
            $label .= html_writer::tag('span', s($kun['reason']), ['class' => 'tz-off']);
        }
        // Bugungi kun sarlavhasi maketdagidek brend-fonda ajralib turadi
        $sarcell = new html_table_cell($label);
        $sarcell->header = true;
        if ($sana === $bugun) {
            $sarcell->attributes['class'] = 'tz-istoday-th';
        }
        $head[] = $sarcell;
    }
    $t->head = $head;
    // Maketning table.tt/myweek jadvali — generaltable terisidan ozod.
    $t->attributes['class'] = 'tz-ttweek';

    for ($i = 1; $i <= $maxles; $i++) {
        // Qator sarlavhasi: para raqami + vaqti (maket: «1 / 08:30»)
        $qator = [html_writer::tag('strong', (string) $i)
            . (isset($paravaqt[$i])
                ? html_writer::div(s($paravaqt[$i]), 'tz-meta') : '')];
        foreach (array_keys(timetable::DAYS) as $d) {
            $sana = $hafta[$d];
            $kun = timetable::school_day($sana, $terms, $holidays);
            // MAKET (myweek): katakning O'ZI «mine» — yashil fon + brend
            // ramka; ichida «Guruh · Fan» va ostida kichik xona/vaqt qatori.
            // Bo'sh katak — och kulrang «—». Butun katak bosilsa kokpit.
            $darslar_katakda = $kun['ok'] ? ($katak[$d][$i] ?? []) : [];
            $ichi = '';
            foreach ($darslar_katakda as $r) {
                $fanline = $isteacherview
                    ? format_string($r->subjectname)
                    : format_string($r->subjectname);
                $guruh = $isteacherview ? format_string($r->groupname) : '';
                // Xona — kartochkani cho'zmaslik uchun fan qatori yoniga
                // kichik yorliq (alohida qator emas). O'quvchi ko'rinishida
                // ustun = o'qituvchi, u pastki qatorda qoladi.
                $xn = [];
                if (!$isteacherview) {
                    $xn[] = $ikkinchi_qator($r);
                }
                // KTP mavzusi — FAQAT o'qituvchining O'Z jadvalida (guruh
                // ko'rinishida va butun maktab panjarasida ko'rinmaydi:
                // o'lchamni buzadi, 2026-11-18 talabi).
                $mv = $isteacherview
                    ? ($mavzuhafta[$sana . '#' . (int) $r->lessonno] ?? null)
                    : null;
                $mvhtml = '';
                if ($mv) {
                    $mvhtml = html_writer::tag('span', format_string($mv[1]),
                        ['class' => 'tz-c-mavzu' . ($mv[0] === 'reja' ? ' tz-reja' : ''),
                         'title' => ($mv[0] === 'reja'
                            ? get_string('jr_rejatitle', 'local_targetzenit', $mv[1])
                            : format_string($mv[1]))]);
                }

                // Fan rangi: barqaror hue → pastel fon + to'q aksent chizig'i
                // (Google Calendar / zamonaviy jadval ilovalari uslubi).
                $h = $hue((string) $r->subjectkey);
                // Fan qatori: fan nomi + o'ngda kichik xona yorlig'i (bitta
                // qator, kartochkani vertikal cho'zmaydi).
                $fanqator = html_writer::tag('span', $fanline, ['class' => 'tz-c-fan'])
                    . ($isteacherview && $r->room
                        ? html_writer::tag('span', s($r->room), ['class' => 'tz-c-room'])
                        : '');
                $karta =
                    ($guruh !== ''
                        ? html_writer::tag('span', $guruh, ['class' => 'tz-c-guruh']) : '')
                    . html_writer::div($fanqator, 'tz-c-fanrow')
                    . $mvhtml
                    . ($xn ? html_writer::tag('span', implode(' · ', $xn),
                        ['class' => 'tz-c-xn']) : '');

                $cid = $courseids[$r->courseidnumber] ?? 0;
                $atr = ['class' => 'tz-card', 'style' => '--h:' . $h,
                        'title' => format_string($r->subjectname) . ' · '
                            . format_string($r->groupname)
                            . ($r->room ? ' · ' . s($r->room) : '')];
                if ($isteacherview && $cid) {
                    // BUTUN kartochka bosiladi — darsni ichiga (kokpit) olib boradi.
                    $atr['href'] = (new moodle_url('/local/targetzenit/dars.php', [
                        'courseid' => $cid, 'date' => $sana,
                        'lessonno' => (int) $r->lessonno]))->out(false);
                    $ichi .= html_writer::tag('a', $karta, $atr);
                } else {
                    $ichi .= html_writer::div($karta, 'tz-card', $atr);
                }
            }

            $katakobj = new html_table_cell($ichi);
            $sinflar2 = ['tz-c-cell'];
            if ($ichi === '') {
                $sinflar2[] = 'tz-c-empty';
            }
            if ($sana === $bugun && $kun['ok']) {
                $sinflar2[] = 'tz-istoday-col';
            }
            $katakobj->attributes['class'] = implode(' ', $sinflar2);
            $qator[] = $katakobj;
        }
        $t->data[] = $qator;
    }
    echo html_writer::div(html_writer::table($t), 'tz-scroll');
}

if ($qobiq) {
    echo html_writer::end_div();   // tz-ishstoli-wrap
    \local_targetzenit\output\maket::tugat();
} else {
    echo $OUTPUT->footer();
}
