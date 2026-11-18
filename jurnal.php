<?php
// Chorak jurnali: chapda o'quvchilar, o'rtada baholar, o'ngda mavzular.

require(__DIR__ . '/../../config.php');

use local_targetzenit\exam;
use local_targetzenit\journal;
use local_targetzenit\lesson;
use local_targetzenit\mavzu;
use local_targetzenit\timetable;

$courseid = optional_param('courseid', 0, PARAM_INT);
$termidx = optional_param('term', -1, PARAM_INT);

require_login();

// Kurs ko'rsatilmagan bo'lsa (top-bar «Jurnal» tabidan to'g'ridan-to'g'ri
// kirilganda) — o'qituvchining BIRINCHI guruhini olamiz; sahifa ichida
// guruh tanlagich bilan boshqasiga o'tiladi.
if ($courseid <= 0) {
    $mening = \local_targetzenit\journal::my_courses((int) $USER->id,
        timetable::current_year());
    if (!$mening) {
        $PAGE->set_context(context_system::instance());
        $PAGE->set_pagelayout('embedded');
        $PAGE->requires->css('/local/targetzenit/styles.css');
        \local_targetzenit\output\maket::boshla('jurnal');
        echo \core\notification::info(get_string('jr_nomycourses', 'local_targetzenit'));
        \local_targetzenit\output\maket::tugat();
        exit;
    }
    $courseid = (int) array_key_first($mening);
}

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/targetzenit:teach', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/jurnal.php', ['courseid' => $courseid]));
// To'liq maket qobig'i (dars.php bilan bir xil model).
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(format_string($course->fullname));
$PAGE->requires->css('/local/targetzenit/styles.css');

// --- O'quv yili va choraklar
$year = timetable::current_year();
$terms = array_values(timetable::terms($year));
if (!$terms) {
    \local_targetzenit\output\maket::boshla('jurnal');
    echo $OUTPUT->notification(get_string('jr_noterms', 'local_targetzenit'), 'warn');
    \local_targetzenit\output\maket::tugat();
    exit;
}

// Standart: bugungi kun qaysi chorakda
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
$term = $terms[$termidx];

$holidays = timetable::holidays($year);
$darslar = journal::lessons_in_term($course->idnumber, $term->startdate, $term->enddate, $holidays);

// Bazada bor, lekin jadvalga tushmagan darslar ham ko'rinsin — aks holda
// ularga qo'yilgan baho jurnalda yo'qolib qoladi.
$darslar = journal::merge_actual($darslar,
    journal::lesson_records($courseid, $term->startdate, $term->enddate));

$students = lesson::students($context);

// ------------------------------------------------------------ EXCEL YUKLASH
if (optional_param('yukla', 0, PARAM_INT) && confirm_sesskey()) {
    $overwrite = optional_param('overwrite', 0, PARAM_INT);
    if (!empty($_FILES['planfile']['tmp_name']) && is_uploaded_file($_FILES['planfile']['tmp_name'])) {
        $nom = clean_param($_FILES['planfile']['name'], PARAM_FILE);
        $vaqtinchalik = make_temp_directory('local_targetzenit') . '/' . uniqid('plan_') . '_' . $nom;
        move_uploaded_file($_FILES['planfile']['tmp_name'], $vaqtinchalik);
        try {
            $reja = journal::read_plan($vaqtinchalik);
            $n = journal::apply_plan($courseid, $darslar, $reja, (int) $USER->id, (bool) $overwrite);
            $xabar = get_string('jr_planresult', 'local_targetzenit', (object) [
                'reja' => $n['reja'],
                'darslar' => $n['darslar'],
                'yozildi' => $n['yozildi'],
                'otkazildi' => $n['otkazildi']
                    ? get_string('jr_planskipped', 'local_targetzenit', $n['otkazildi'])
                    : '',
            ]);
            $turi = \core\output\notification::NOTIFY_SUCCESS;
        } catch (\Throwable $e) {
            $xabar = get_string('jr_filefailed', 'local_targetzenit',
                $e->getMessage());
            $turi = \core\output\notification::NOTIFY_ERROR;
        }
        @unlink($vaqtinchalik);
    } else {
        $xabar = get_string('jr_nofile', 'local_targetzenit');
        $turi = \core\output\notification::NOTIFY_ERROR;
    }
    redirect(new moodle_url('/local/targetzenit/jurnal.php',
        ['courseid' => $courseid, 'term' => $termidx]), $xabar, null, $turi);
}

// ------------------------------------------------------------------ SAQLASH
if (optional_param('saqla', 0, PARAM_INT) && confirm_sesskey()) {
    $kataklar = optional_param_array('cell', [], PARAM_RAW_TRIMMED);
    $mavzular = optional_param_array('topic', [], PARAM_TEXT);
    $vazifalar = optional_param_array('homework', [], PARAM_TEXT);

    $xato = 0;
    foreach ($darslar as $l) {
        $pk = journal::pkey($l);
        $dars = lesson::get_or_create($courseid, $l['date'], (int) $l['lessonno'], (int) $USER->id);

        if (isset($mavzular[$pk]) || isset($vazifalar[$pk])) {
            lesson::save_lesson((int) $dars->id, $mavzular[$pk] ?? '', $vazifalar[$pk] ?? '', (int) $USER->id);
        }

        foreach ($students as $s) {
            $nom = $pk . '_' . $s->id;
            if (!isset($kataklar[$nom])) {
                continue;
            }
            $q = journal::parse_cell((string) $kataklar[$nom]);
            if ($q === null) {
                $xato++;
                continue;
            }
            lesson::save_record((int) $dars->id, (int) $s->id, $q['attendance'], $q['grade'], '');
        }
    }
    // Baholar Moodle gradebook'ida KO'RINISHI uchun kurs yakuniy baholarini
    // bir marta qayta hisoblaymiz (har katakda qilish juda qimmat).
    try {
        \local_targetzenit\gradebook::regrade($courseid);
    } catch (\Throwable $e) {
        \local_targetzenit\gradebook::xato_yoz($e, "regrade kurs $courseid");
    }

    $xabar = $xato
        ? get_string('jr_savedskipped', 'local_targetzenit', $xato)
        : get_string('jr_saved', 'local_targetzenit');
    redirect(new moodle_url('/local/targetzenit/jurnal.php',
        ['courseid' => $courseid, 'term' => $termidx]), $xabar, null,
        $xato ? \core\output\notification::NOTIFY_WARNING : \core\output\notification::NOTIFY_SUCCESS);
}

// ------------------------------------------------------------------ KO'RSATISH
$darsyozuv = journal::lesson_records($courseid, $term->startdate, $term->enddate);
$lessonids = [];
foreach ($darslar as $l) {
    if (isset($darsyozuv[$l['key']])) {
        $lessonids[] = (int) $darsyozuv[$l['key']]->id;
    }
}
$kataklar = journal::cells($lessonids);

// ---------------------------------------------- IMTIHON USTUNLARI VA KTP
// Imtihon ustunlari jadval darslaridan ALOHIDA keladi (`exam::journal_columns`
// izohi: imtihon kuni `lessons_in_term` ro'yxatida bo'lmasligi mumkin) va
// quyida sana tartibida dars ustunlari orasiga qo'shiladi. Ball bu yerda
// KIRITILMAYDI — kiritish ball setkasida qoladi, jurnal faqat ko'rsatadi.
$imtihonlar = exam::journal_columns($courseid, $year, $term->name);
$imtihonnatija = exam::results_for(array_map(
    static fn($e) => (int) $e->sittingid, $imtihonlar));
$choraknatija = exam::term_scores($courseid, $year, $term->name);

// Ustunlar: dars va imtihon aralash, sana tartibida. Bir sanada avval dars,
// keyin imtihon turadi. `usort` barqaror (PHP 8) — bir kunlik darslar
// `lessons_in_term` bergan para tartibini saqlaydi.
$ustunlar = [];
foreach ($darslar as $l) {
    $ustunlar[] = ['sana' => $l['date'], 'keyin' => 0, 'dars' => $l];
}
foreach ($imtihonlar as $e) {
    $ustunlar[] = ['sana' => (string) $e->sana, 'keyin' => 1, 'imtihon' => $e];
}
usort($ustunlar, static fn($a, $b) =>
    [$a['sana'], $a['keyin']] <=> [$b['sana'], $b['keyin']]);

// KTP reja mavzulari — mavzu katagi bo'sh bo'lsa placeholder bo'lib turadi
// (reja taklif qiladi, yakuniy so'z o'qituvchida). Bitta so'rov.
$rejamavzu = [];
$rejaslotlar = $DB->get_records_sql("
    SELECT s.id, s.lessondate, s.lessonno, s.mavzu
      FROM {local_targetzenit_slot} s
     WHERE s.courseid = :c AND s.mavzuid > 0
       AND s.lessondate >= :boshi AND s.lessondate <= :oxiri",
    ['c' => $courseid, 'boshi' => $term->startdate, 'oxiri' => $term->enddate]);
foreach ($rejaslotlar as $rs) {
    $rejamavzu[$rs->lessondate . '#' . (int) $rs->lessonno] = trim((string) $rs->mavzu);
}

\local_targetzenit\output\maket::boshla('jurnal');

// Maket kanvasi + khead.
echo html_writer::start_div('tz-ishstoli-wrap tz-kokpit');
echo html_writer::tag('div', format_string($course->fullname)
    . ' — ' . format_string($term->name), ['class' => 'tzp-h1 mb-2']);

// Qaytish yo'li — chorak jurnali boshi berk ko'cha bo'lib qolmasin.
// `Lessons` shu kursning JORIY kuniga emas, umuman bugungi darslarga olib
// boradi: o'qituvchi jurnaldan chiqqanda odatda keyingi darsiga o'tadi.
echo html_writer::div(
    html_writer::link(new moodle_url('/local/targetzenit/index.php',
        ['view' => 'today', 'date' => date('Y-m-d')]),
        '← ' . get_string('lessons_link', 'local_targetzenit'),
        ['class' => 'btn btn-sm btn-outline-secondary me-2'])
    . html_writer::link(new moodle_url('/local/targetzenit/index.php', ['view' => 'week']),
        get_string('timetable', 'local_targetzenit'),
        ['class' => 'btn btn-sm btn-outline-secondary']),
    'mb-3');

// ------------------------------------------------- SINF VA CHORAK TANLASH
// Ikkalasi bitta qatorda: o'qituvchi jurnalni ochganda birinchi qiladigan
// ishi — "qaysi sinf, qaysi chorak" ni to'g'rilash.
echo html_writer::start_div('tz-jurnal-tanlov mb-2');

// --- Sinf (kurs) tanlash.
//
// NEGA KERAK: avval kursni almashtirish uchun kurs sahifasidan qaytib,
// boshqa kursga kirib, u yerdan jurnalni ochish kerak edi — 1-A dan 10-A ga
// o'tish uch bosqich. Jurnal esa aynan sinfdan sinfga ko'chib yuriladigan
// ekran.
//
// Chorak SAQLANADI: sinf almashsa ham o'qituvchi o'sha chorakda qoladi,
// aks holda har almashtirishda joriy chorakka qaytarib tashlardi.
$kurslar = journal::my_courses((int) $USER->id, $year);
if (count($kurslar) > 1) {
    $kurstanlov = new single_select(
        new moodle_url('/local/targetzenit/jurnal.php', ['term' => $termidx]),
        'courseid', $kurslar, $courseid);
    $kurstanlov->set_label(get_string('jurnal_class', 'local_targetzenit') . ': ');
    echo $OUTPUT->render($kurstanlov);
}

// --- Chorak tanlash
$options = [];
foreach ($terms as $i => $t) {
    $options[$i] = $t->name . ' (' . $t->startdate . ' – ' . $t->enddate . ')';
}
$select = new single_select(
    new moodle_url('/local/targetzenit/jurnal.php', ['courseid' => $courseid]),
    'term', $options, $termidx);
$select->set_label(get_string('jurnal_term', 'local_targetzenit') . ': ');
echo $OUTPUT->render($select);

echo html_writer::end_div();


// Ikki ko'rinish almashtirgich (maket): baholar setkasi / barcha mavzular+UV.
echo html_writer::div(
    html_writer::tag('button', get_string('jr_view_grid', 'local_targetzenit'),
        ['type' => 'button', 'class' => 'tz-vtab tz-on', 'id' => 'tz-jv-s',
         'data-jv' => 's'])
    . html_writer::tag('button', get_string('jr_view_topics', 'local_targetzenit'),
        ['type' => 'button', 'class' => 'tz-vtab', 'id' => 'tz-jv-t',
         'data-jv' => 't']),
    'tz-vtabs mb-3');

echo html_writer::div(
    html_writer::tag('span',
        get_string('jr_celllegend', 'local_targetzenit', lesson::MAX_GRADE),
        ['class' => 'text-muted small'])
    . ($imtihonlar
        ? ' ' . html_writer::tag('span',
            get_string('jr_examlegend', 'local_targetzenit'),
            ['class' => 'text-muted small'])
        : ''),
    'mb-3', ['id' => 'tz-jv-legend']
);

echo html_writer::start_div('', ['id' => 'tz-jur-s']);   // baholar setkasi ko'rinishi

if (!$darslar) {
    echo $OUTPUT->notification(get_string('jr_nolessons', 'local_targetzenit'), 'info');
    echo html_writer::end_div();   // tz-ishstoli-wrap
    \local_targetzenit\output\maket::tugat();
    exit;
}

// Bugungi kun shu chorakdami? Shunga qarab setka va mavzular ro'yxati
// ochilganda o'zi bugungi joyga suriladi (eskisi skrol bilan ochiladi).
$bugun = date('Y-m-d');
$bugungi_kalit = null;
foreach ($darslar as $l) {
    if ($l['date'] === $bugun) {
        $bugungi_kalit = $l['key'];
        break;
    }
}
// Bugun dars bo'lmasa (bayram/dam olish) — eng yaqin keyingi darsni olamiz
if ($bugungi_kalit === null) {
    foreach ($darslar as $l) {
        if ($l['date'] >= $bugun) {
            $bugungi_kalit = $l['key'];
            break;
        }
    }
}

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'term', 'value' => $termidx]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'saqla', 'value' => 1]);

echo html_writer::start_div('tz-jurnal-wrap');

// ---------------- CHAP + O'RTA: o'quvchilar va baholar
echo html_writer::start_div('tz-jurnal-grid');
echo html_writer::start_tag('table', ['class' => 'generaltable tz-jgrid']);

echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', '#', ['class' => 'tz-col-n']);
echo html_writer::tag('th', get_string('student', 'local_targetzenit'),
    ['class' => 'tz-col-ism']);
// Ball setkasiga havola faqat baholash huquqi borlarga ko'rsatiladi.
$ballhuquq = has_capability('local/targetzenit:gradeexam', $context);
// Ustun indeksi — E-maktab uslubidagi KUN TANLASH uchun: sana ustuni
// bosilganda o'sha ustun va o'ngdagi mavzu qatori birga ajraladi.
$ci = -1;
$bugunci = -1;
foreach ($ustunlar as $ustun) {
    $ci++;
    if (isset($ustun['imtihon'])) {
        $e = $ustun['imtihon'];
        $turi = s($e->turi);
        if ($ballhuquq) {
            $turi = html_writer::link(
                new moodle_url('/local/targetzenit/baholash.php',
                    ['view' => 'ball', 'sitting' => (int) $e->sittingid]),
                $turi, ['class' => 'tz-exam-link']);
        }
        echo html_writer::tag('th',
            html_writer::div(userdate(strtotime($e->sana), '%d.%m'), 'tz-j-date') .
            html_writer::div($turi, 'tz-meta'),
            ['class' => 'tz-col-kun tz-col-exam', 'data-col' => $ci,
             'title' => get_string('jr_examtitle', 'local_targetzenit', (object) [
                 'name' => $e->name, 'sana' => $e->sana,
                 'max' => $e->maxscore])]);
        continue;
    }
    $l = $ustun['dars'];
    $sana = userdate(strtotime($l['date']), '%d.%m');
    $bu = ($l['key'] === $bugungi_kalit);
    if ($bu) {
        $bugunci = $ci;
    }
    $tashqari = !empty($l['jadvaldan_tashqari']);
    echo html_writer::tag('th',
        html_writer::div($sana, 'tz-j-date') .
        html_writer::div(
            get_string('jr_lessonshort', 'local_targetzenit', $l['lessonno'])
            . ($tashqari ? ' *' : ''), 'tz-meta'),
        ['class' => 'tz-col-kun' . ($bu ? ' tz-bugun-col' : '') . ($tashqari ? ' tz-tashqari' : ''),
         'data-col' => $ci,
         'title' => $tashqari
             ? get_string('jr_offschedule', 'local_targetzenit') : ''] +
        ($bu ? ['id' => 'tz-bugun-ustun'] : []));
}
// Oxirida — taklif chorak bahosi (imtihon yoki saqlangan natija bo'lsagina).
$chorakustun = ($imtihonlar || $choraknatija);
if ($chorakustun) {
    echo html_writer::tag('th',
        html_writer::div(get_string('jr_termcol', 'local_targetzenit'), 'tz-j-date'),
        ['class' => 'tz-col-kun tz-col-term',
         'title' => get_string('jr_termtitle', 'local_targetzenit')]);
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');

echo html_writer::start_tag('tbody');
$n = 0;
foreach ($students as $s) {
    $n++;
    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', $n, ['class' => 'tz-col-n']);
    echo html_writer::tag('td',
        html_writer::link(
            new moodle_url('/local/targetzenit/oquvchi360.php', ['userid' => $s->id]),
            fullname($s),
            ['title' => get_string('s360_open', 'local_targetzenit', fullname($s))]),
        ['class' => 'tz-col-ism']);
    $ci = -1;
    foreach ($ustunlar as $ustun) {
        $ci++;
        if (isset($ustun['imtihon'])) {
            $e = $ustun['imtihon'];
            $r = $imtihonnatija[(int) $e->sittingid][(int) $s->id] ?? null;
            $matn = '';
            $title = '';
            if ($r && $r->score !== null) {
                // 42.0 -> «42», 38.25 -> «38.3» — katak tor, ortiqcha nol yo'q.
                $q = (float) $r->score;
                $matn = ($q == (int) $q) ? (string) (int) $q : (string) round($q, 1);
                if (!empty($r->capped)) {
                    $title = get_string('bh_capped', 'local_targetzenit');
                }
            } else if ($r && $r->holat) {
                // Ball yo'q, lekin holat bor (uzrli/uzrsiz/...) — birinchi
                // harfi ko'rinadi, to'lig'i title'da.
                $matn = mb_substr((string) $r->holat, 0, 1);
                $title = (string) $r->holat;
            }
            echo html_writer::tag('td', s($matn),
                ['class' => 'tz-col-kun tz-col-exam tz-exam-cell',
                 'data-col' => $ci, 'title' => $title]);
            continue;
        }
        $l = $ustun['dars'];
        $dars = $darsyozuv[$l['key']] ?? null;
        $rec = ($dars && isset($kataklar[$s->id][$dars->id])) ? $kataklar[$s->id][$dars->id] : null;
        echo html_writer::tag('td',
            html_writer::empty_tag('input', [
                'type' => 'text',
                'name' => 'cell[' . journal::pkey($l) . '_' . $s->id . ']',
                'value' => journal::cell_text($rec),
                'class' => 'tz-cell',
                'maxlength' => 4,
                'autocomplete' => 'off',
            ]),
            ['class' => 'tz-col-kun' . ($l['key'] === $bugungi_kalit ? ' tz-bugun-col' : ''),
             'data-col' => $ci]);
    }
    if ($chorakustun) {
        $ts = $choraknatija[(int) $s->id] ?? null;
        $matn = ($ts && $ts->total !== null)
            ? $ts->total . ' → ' . $ts->baho . ((int) $ts->frozen === 1 ? ' ✓' : '')
            : '—';
        echo html_writer::tag('td', $matn,
            ['class' => 'tz-col-kun tz-term-cell']);
    }
    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');
echo html_writer::end_div();

// ---------------- O'NG: chorak mavzulari
echo html_writer::start_div('tz-jurnal-mavzu');
echo html_writer::tag('h5',
    get_string('jr_topics', 'local_targetzenit', format_string($term->name)));

// KTP qamrovi: shu chorak rejasidan nechta mavzu o'tildi. Guruh va fan
// kaliti kurs idnumber'idan: "{yil}::{guruh}::{fan}".
[, $guruhkalit, $fankalit] = array_pad(explode('::', (string) $course->idnumber), 3, '');
if ($fankalit !== '') {
    $qamrov = mavzu::qamrov($year, $fankalit, $guruhkalit,
        mavzu::chorak_raqami($term->name));
    if ($qamrov['jami'] > 0) {
        echo html_writer::div(
            get_string('jr_ktp', 'local_targetzenit', (object) $qamrov),
            'tz-ktp-qamrov');
    }
}

echo html_writer::start_div('tz-mavzu-scroll', ['id' => 'tz-mavzu-scroll']);
echo html_writer::start_tag('table', ['class' => 'generaltable tz-mavzu']);
echo html_writer::tag('thead',
    html_writer::tag('tr',
        html_writer::tag('th', get_string('jr_date', 'local_targetzenit')) .
        html_writer::tag('th', get_string('jr_topichw', 'local_targetzenit'))));
echo html_writer::start_tag('tbody');
$raqam = 0;
$ci = -1;
foreach ($ustunlar as $ustun) {
    $ci++;
    // Imtihon qatori: tahrirlanmaydi, jurnal ustuni bilan bir xil tartibda
    // turadi — o'qituvchi chorakni yuqoridan ko'rganda imtihonlar mavzular
    // oqimining ichida ko'rinadi.
    if (isset($ustun['imtihon'])) {
        $e = $ustun['imtihon'];
        $sana = userdate(strtotime($e->sana), '%d.%m')
            . ($e->vaqt ? ' · ' . s($e->vaqt) : '');
        echo html_writer::start_tag('tr', ['class' => 'tz-m-imtihon', 'data-col' => $ci]);
        echo html_writer::tag('td', html_writer::div($sana, 'tz-meta'),
            ['class' => 'tz-m-sana']);
        echo html_writer::tag('td',
            html_writer::tag('b', get_string('jr_examrow', 'local_targetzenit',
                (object) ['turi' => $e->turi, 'name' => format_string($e->name)]))
            . html_writer::div(
                get_string('jr_exammeta', 'local_targetzenit', (object) [
                    'max' => $e->maxscore,
                    'shakli' => $e->shakli,
                ]), 'tz-meta'));
        echo html_writer::end_tag('tr');
        continue;
    }
    $l = $ustun['dars'];
    $raqam++;
    $dars = $darsyozuv[$l['key']] ?? null;
    $sana = userdate(strtotime($l['date']), '%d.%m') . ' · '
        . get_string('jr_lessonshort', 'local_targetzenit', $l['lessonno']);
    $bu = ($l['key'] === $bugungi_kalit);
    // Mavzu bo'sh bo'lsa KTP rejasi placeholder bo'lib turadi — o'qituvchi
    // rejani ko'radi, lekin fakt faqat o'zi yozganda saqlanadi.
    $reja = $rejamavzu[$l['key']] ?? '';
    echo html_writer::start_tag('tr',
        ['class' => $bu ? 'tz-bugun-qator' : '', 'data-col' => $ci]
        + ($bu ? ['id' => 'tz-bugun-mavzu'] : []));
    echo html_writer::tag('td', html_writer::div($raqam . '. ' . $sana, 'tz-meta'), ['class' => 'tz-m-sana']);
    echo html_writer::tag('td',
        html_writer::empty_tag('input', [
            'type' => 'text', 'name' => 'topic[' . journal::pkey($l) . ']',
            'value' => $dars->topic ?? '', 'class' => 'form-control form-control-sm mb-1',
            'placeholder' => $reja !== ''
                ? $reja : get_string('jr_topicph', 'local_targetzenit'),
        ] + ($reja !== ''
            ? ['title' => get_string('jr_rejatitle', 'local_targetzenit', $reja)]
            : [])) .
        html_writer::empty_tag('input', [
            'type' => 'text', 'name' => 'homework[' . journal::pkey($l) . ']',
            'value' => $dars->homework ?? '', 'class' => 'form-control form-control-sm tz-hw-input',
            'placeholder' => get_string('jr_hwph', 'local_targetzenit'),
        ]));
    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');
echo html_writer::end_div();   // tz-mavzu-scroll
echo html_writer::end_div();   // tz-jurnal-mavzu

echo html_writer::end_div();   // wrap

// E-MAKTAB USLUBIDAGI KUN TANLASH (maket, 7.5-qaror): sana ustuni yoki
// o'ngdagi mavzu qatori bosilganda IKKISI BIRGA to'qroq rang bilan ajraladi
// va bir-biriga suriladi — o'qituvchi "bu baho qaysi mavzuga tegishli" degan
// savolga bir qarashda javob oladi. Ochilganda bugungi kun tanlangan bo'ladi.
// Sahifa umuman pastga sakramasligi uchun scrollIntoView ishlatilmaydi —
// faqat ichki qutilar suriladi.
$PAGE->requires->js_amd_inline("
    require([], function() {
        var tanla = function(i, sur) {
            document.querySelectorAll('.tz-jgrid [data-col]').forEach(function(el) {
                el.classList.toggle('tz-sel', el.getAttribute('data-col') === String(i));
            });
            document.querySelectorAll('.tz-mavzu tr[data-col]').forEach(function(el) {
                el.classList.toggle('tz-sel-row', el.getAttribute('data-col') === String(i));
            });
            if (!sur) { return; }
            var ustun = document.querySelector('.tz-jgrid thead th[data-col=\"' + i + '\"]');
            var setka = document.querySelector('.tz-jurnal-grid');
            if (ustun && setka) {
                var qotgan = 224;   // chapdagi sticky ustunlar kengligi
                setka.scrollLeft = Math.max(0, ustun.offsetLeft - qotgan - 40);
            }
            var qator = document.querySelector('.tz-mavzu tr[data-col=\"' + i + '\"]');
            var quti = document.getElementById('tz-mavzu-scroll');
            if (qator && quti) {
                quti.scrollTop = Math.max(0, qator.offsetTop - quti.offsetTop - 30);
            }
        };

        document.querySelectorAll('.tz-jgrid thead th[data-col]').forEach(function(th) {
            th.addEventListener('click', function() {
                tanla(th.getAttribute('data-col'), true);
            });
        });
        document.querySelectorAll('.tz-mavzu tr[data-col]').forEach(function(tr) {
            tr.addEventListener('click', function(e) {
                // Input ichiga yozayotganda tanlov sakrab ketmasin
                if (e.target.tagName === 'INPUT') {
                    tanla(tr.getAttribute('data-col'), false);
                    return;
                }
                tanla(tr.getAttribute('data-col'), true);
            });
        });

        var boshlangich = " . (int) $bugunci . ";

        // BAHO RANGI: katak qiymatiga qarab yashil/ko'k/sariq/qizil. Raqam
        // baho (9-10=a'lo, 7-8=yaxshi, 5-6=o'rta, past=qizil); «k» kelmadi
        // (qizil), «s» sababli (neytral), «kech» kechikdi (sariq).
        var rangla = function(inp) {
            var v = (inp.value || '').trim().toLowerCase();
            inp.classList.remove('tz-g5', 'tz-g4', 'tz-g3', 'tz-gN');
            if (v === '') { return; }
            var n = parseInt(v, 10);
            if (!isNaN(n) && String(n) === v) {
                if (n >= 9) { inp.classList.add('tz-g5'); }
                else if (n >= 7) { inp.classList.add('tz-g4'); }
                else if (n >= 5) { inp.classList.add('tz-g3'); }
                else { inp.classList.add('tz-gN'); }
            } else if (v === 'k') {
                inp.classList.add('tz-gN');
            } else if (v.indexOf('kech') === 0) {
                inp.classList.add('tz-g3');
            }
        };

        var ishga = function() {
            if (boshlangich >= 0) { tanla(boshlangich, true); }
            document.querySelectorAll('.tz-jgrid .tz-cell').forEach(function(inp) {
                rangla(inp);
                inp.addEventListener('input', function() { rangla(inp); });
            });
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', ishga);
        } else {
            ishga();
        }
    });
");

echo html_writer::div(
    html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('savechanges'),
        'class' => 'btn btn-primary btn-lg']),
    'my-3'
);
echo html_writer::end_tag('form');

// ---------------- Excel yuklash
echo html_writer::start_div('card mt-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', get_string('jr_upload', 'local_targetzenit'));
echo html_writer::tag('p',
    get_string('jr_uploadhint', 'local_targetzenit', count($darslar)),
    ['class' => 'text-muted small']);
echo html_writer::start_tag('form', ['method' => 'post', 'enctype' => 'multipart/form-data',
    'action' => $PAGE->url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'term', 'value' => $termidx]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'yukla', 'value' => 1]);
echo html_writer::empty_tag('input', ['type' => 'file', 'name' => 'planfile',
    'accept' => '.xlsx,.xls,.csv', 'class' => 'form-control mb-2']);
echo html_writer::div(
    html_writer::checkbox('overwrite', 1, false,
        get_string('jr_overwrite', 'local_targetzenit')),
    'mb-2 small');
echo html_writer::empty_tag('input', ['type' => 'submit',
    'value' => get_string('jr_uploadbtn', 'local_targetzenit'),
    'class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();   // tz-jur-s (baholar setkasi ko'rinishi)

// ============ «BARCHA MAVZULAR VA UV» KO'RINISHI (maket, faqat-o'qish) ============
// Chorakning har bir darsi uchun bitta qator: #, sana, mavzu (fakt yoki
// reja), uy vazifa, holat (o'tildi/bugun/kelgusi/imtihon), jurnal to'ldirilishi
// (nechta baho). Tahrir setka ko'rinishida — bu yer umumiy ko'rinish.
echo html_writer::start_div('tzp-card', ['id' => 'tz-jur-t', 'hidden' => 'hidden']);
echo html_writer::tag('h5',
    get_string('jr_view_topics', 'local_targetzenit')
    . html_writer::tag('span', (string) count($darslar), ['class' => 'tzp-cnt ms-2']));

echo html_writer::start_div('', ['style' => 'overflow-x:auto']);
echo html_writer::start_tag('table', ['class' => 'tz-mavjad']);
echo html_writer::tag('thead', html_writer::tag('tr',
    html_writer::tag('th', '#')
    . html_writer::tag('th', get_string('jr_date', 'local_targetzenit'))
    . html_writer::tag('th', get_string('topic', 'local_targetzenit'))
    . html_writer::tag('th', get_string('homework', 'local_targetzenit'))
    . html_writer::tag('th', get_string('jr_m_status', 'local_targetzenit'))
    . html_writer::tag('th', get_string('jr_m_filled', 'local_targetzenit'))));
echo html_writer::start_tag('tbody');

$bugunsana = date('Y-m-d');
$tn = 0;
foreach ($ustunlar as $ustun) {
    if (isset($ustun['imtihon'])) {
        $e = $ustun['imtihon'];
        echo html_writer::tag('tr',
            html_writer::tag('td', '')
            . html_writer::tag('td', userdate(strtotime($e->sana), '%d.%m')
                . ($e->vaqt ? '<br>' . s($e->vaqt) : ''))
            . html_writer::tag('td',
                html_writer::tag('b', s($e->turi) . ': ' . format_string($e->name)),
                ['colspan' => 2])
            . html_writer::tag('td',
                html_writer::tag('span', get_string('jr_m_exam', 'local_targetzenit'),
                    ['class' => 'tz-mst tz-mst-exam']))
            . html_writer::tag('td',
                $e->maxscore ? get_string('jr_exammeta', 'local_targetzenit',
                    (object) ['max' => $e->maxscore, 'shakli' => $e->shakli]) : ''),
            ['class' => 'tz-m-exam-row']);
        continue;
    }
    $l = $ustun['dars'];
    $tn++;
    $dars = $darsyozuv[$l['key']] ?? null;
    $faktmavzu = $dars && trim((string) $dars->topic) !== ''
        ? format_string($dars->topic) : '';
    $reja = $rejamavzu[$l['key']] ?? '';
    $mavzumatn = $faktmavzu !== ''
        ? $faktmavzu
        : ($reja !== ''
            ? html_writer::tag('span', format_string($reja),
                ['class' => 'tz-m-reja',
                 'title' => get_string('jr_rejatitle', 'local_targetzenit', $reja)])
            : html_writer::tag('span', '—', ['class' => 'text-muted']));
    $uv = $dars && trim((string) $dars->homework) !== ''
        ? format_string($dars->homework)
        : html_writer::tag('span', '—', ['class' => 'text-muted']);

    // Holat: o'tildi (fakt bor) / bugun / kelgusi
    if ($l['date'] < $bugunsana || ($faktmavzu !== '')) {
        $holat = html_writer::tag('span', get_string('jr_m_done', 'local_targetzenit'),
            ['class' => 'tz-mst tz-mst-done']);
    } else if ($l['date'] === $bugunsana) {
        $holat = html_writer::tag('span', get_string('jr_m_today', 'local_targetzenit'),
            ['class' => 'tz-mst tz-mst-today']);
    } else {
        $holat = html_writer::tag('span', get_string('jr_m_future', 'local_targetzenit'),
            ['class' => 'tz-mst tz-mst-future']);
    }

    // Jurnal to'ldirilishi: shu darsда nechta baho qo'yilgan
    $baho = 0;
    if ($dars && isset($kataklar)) {
        foreach ($students as $s) {
            $rec = $kataklar[$s->id][$dars->id] ?? null;
            if ($rec && $rec->grade !== null) {
                $baho++;
            }
        }
    }
    $filled = $baho > 0
        ? get_string('jr_m_grades', 'local_targetzenit', $baho)
        : html_writer::tag('span', '—', ['class' => 'text-muted']);

    $bu = ($l['key'] === $bugungi_kalit);
    echo html_writer::tag('tr',
        html_writer::tag('td', (string) $tn, ['class' => 'tz-m-num'])
        . html_writer::tag('td', userdate(strtotime($l['date']), '%d.%m') . '<br>'
            . html_writer::tag('small',
                get_string('jr_lessonshort', 'local_targetzenit', $l['lessonno']),
                ['class' => 'text-muted']))
        . html_writer::tag('td', $mavzumatn)
        . html_writer::tag('td', $uv)
        . html_writer::tag('td', $holat)
        . html_writer::tag('td', $filled),
        $bu ? ['class' => 'tz-m-today-row'] : []);
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');
echo html_writer::end_div();
echo html_writer::end_div();   // tz-jur-t

// Ko'rinish almashtirgich JS.
$PAGE->requires->js_amd_inline("
    require([], function() {
        var s = document.getElementById('tz-jur-s');
        var t = document.getElementById('tz-jur-t');
        var lg = document.getElementById('tz-jv-legend');
        var bs = document.getElementById('tz-jv-s');
        var bt = document.getElementById('tz-jv-t');
        if (!s || !t) { return; }
        var ko = function(v) {
            s.hidden = (v !== 's');
            t.hidden = (v !== 't');
            if (lg) { lg.style.display = (v === 's') ? '' : 'none'; }
            bs.classList.toggle('tz-on', v === 's');
            bt.classList.toggle('tz-on', v === 't');
        };
        bs.addEventListener('click', function() { ko('s'); });
        bt.addEventListener('click', function() { ko('t'); });
    });
");

echo html_writer::end_div();   // tz-ishstoli-wrap (kanvas)

\local_targetzenit\output\maket::tugat();
