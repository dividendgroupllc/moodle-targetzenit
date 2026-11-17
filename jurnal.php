<?php
// Chorak jurnali: chapda o'quvchilar, o'rtada baholar, o'ngda mavzular.

require(__DIR__ . '/../../config.php');

use local_targetzenit\exam;
use local_targetzenit\journal;
use local_targetzenit\lesson;
use local_targetzenit\mavzu;
use local_targetzenit\timetable;

$courseid = required_param('courseid', PARAM_INT);
$termidx = optional_param('term', -1, PARAM_INT);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/targetzenit:teach', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/jurnal.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

// --- O'quv yili va choraklar
$year = timetable::current_year();
$terms = array_values(timetable::terms($year));
if (!$terms) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('jr_noterms', 'local_targetzenit'), 'warn');
    echo $OUTPUT->footer();
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

echo $OUTPUT->header();

// Qaytish yo'li — chorak jurnali boshi berk ko'cha bo'lib qolmasin.
// `Lessons` shu kursning JORIY kuniga emas, umuman bugungi darslarga olib
// boradi: o'qituvchi jurnaldan chiqqanda odatda keyingi darsiga o'tadi.
echo html_writer::div(
    html_writer::link(new moodle_url('/local/targetzenit/index.php',
        ['view' => 'today', 'date' => date('Y-m-d')]),
        '← ' . get_string('lessons_link', 'local_targetzenit'),
        ['class' => 'btn btn-sm btn-outline-secondary mr-2'])
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

echo html_writer::div(local_targetzenit_zoom_panel('jurnal'), 'mb-2');

echo html_writer::div(
    html_writer::tag('span',
        get_string('jr_celllegend', 'local_targetzenit', lesson::MAX_GRADE),
        ['class' => 'text-muted small'])
    . ($imtihonlar
        ? ' ' . html_writer::tag('span',
            get_string('jr_examlegend', 'local_targetzenit'),
            ['class' => 'text-muted small'])
        : ''),
    'mb-3'
);

if (!$darslar) {
    echo $OUTPUT->notification(get_string('jr_nolessons', 'local_targetzenit'), 'info');
    echo $OUTPUT->footer();
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
foreach ($ustunlar as $ustun) {
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
            ['class' => 'tz-col-kun tz-col-exam',
             'title' => get_string('jr_examtitle', 'local_targetzenit', (object) [
                 'name' => $e->name, 'sana' => $e->sana,
                 'max' => $e->maxscore])]);
        continue;
    }
    $l = $ustun['dars'];
    $sana = userdate(strtotime($l['date']), '%d.%m');
    $bu = ($l['key'] === $bugungi_kalit);
    $tashqari = !empty($l['jadvaldan_tashqari']);
    echo html_writer::tag('th',
        html_writer::div($sana, 'tz-j-date') .
        html_writer::div(
            get_string('jr_lessonshort', 'local_targetzenit', $l['lessonno'])
            . ($tashqari ? ' *' : ''), 'tz-meta'),
        ['class' => 'tz-col-kun' . ($bu ? ' tz-bugun-col' : '') . ($tashqari ? ' tz-tashqari' : ''),
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
            new moodle_url('/local/targetzenit/baholarim.php', ['userid' => $s->id]),
            fullname($s),
            ['title' => get_string('jr_studentgrades', 'local_targetzenit', fullname($s))]),
        ['class' => 'tz-col-ism']);
    foreach ($ustunlar as $ustun) {
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
                 'title' => $title]);
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
            ['class' => 'tz-col-kun' . ($l['key'] === $bugungi_kalit ? ' tz-bugun-col' : '')]);
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
foreach ($ustunlar as $ustun) {
    // Imtihon qatori: tahrirlanmaydi, jurnal ustuni bilan bir xil tartibda
    // turadi — o'qituvchi chorakni yuqoridan ko'rganda imtihonlar mavzular
    // oqimining ichida ko'rinadi.
    if (isset($ustun['imtihon'])) {
        $e = $ustun['imtihon'];
        $sana = userdate(strtotime($e->sana), '%d.%m')
            . ($e->vaqt ? ' · ' . s($e->vaqt) : '');
        echo html_writer::start_tag('tr', ['class' => 'tz-m-imtihon']);
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
        ['class' => $bu ? 'tz-bugun-qator' : ''] + ($bu ? ['id' => 'tz-bugun-mavzu'] : []));
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

// Ochilganda bugungi kunga surish. Sahifa umuman pastga sakramasligi uchun
// scrollIntoView ishlatilmaydi — faqat ichki qutilar suriladi.
if ($bugungi_kalit !== null) {
    $PAGE->requires->js_amd_inline("
        require([], function() {
            var surish = function() {
                var ustun = document.getElementById('tz-bugun-ustun');
                var setka = document.querySelector('.tz-jurnal-grid');
                if (ustun && setka) {
                    var qotgan = 224;   // chapdagi sticky ustunlar kengligi
                    setka.scrollLeft = Math.max(0, ustun.offsetLeft - qotgan - 40);
                }
                var qator = document.getElementById('tz-bugun-mavzu');
                var quti = document.getElementById('tz-mavzu-scroll');
                if (qator && quti) {
                    quti.scrollTop = Math.max(0, qator.offsetTop - quti.offsetTop - 30);
                }
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', surish);
            } else {
                surish();
            }
        });
    ");
}

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
    'accept' => '.xlsx,.xls,.csv', 'class' => 'form-control-file mb-2']);
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

echo $OUTPUT->footer();
