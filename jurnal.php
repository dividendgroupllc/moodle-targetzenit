<?php
// Chorak jurnali: chapda o'quvchilar, o'rtada baholar, o'ngda mavzular.

require(__DIR__ . '/../../config.php');

use local_targetzenit\journal;
use local_targetzenit\lesson;
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
    echo $OUTPUT->notification("Chorak sanalari topilmadi — Frappe'dan jadval va kalendar yuborilmagan.", 'warn');
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
            $xabar = "Rejada {$n['reja']} mavzu, chorakda {$n['darslar']} dars. " .
                "Yozildi: {$n['yozildi']}" . ($n['otkazildi'] ? ", tegilmadi (allaqachon to'ldirilgan): {$n['otkazildi']}" : '');
            $turi = \core\output\notification::NOTIFY_SUCCESS;
        } catch (\Throwable $e) {
            $xabar = 'Faylni o\'qib bo\'lmadi: ' . $e->getMessage();
            $turi = \core\output\notification::NOTIFY_ERROR;
        }
        @unlink($vaqtinchalik);
    } else {
        $xabar = 'Fayl tanlanmadi.';
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
        $k = $l['key'];
        $dars = lesson::get_or_create($courseid, $l['date'], (int) $l['lessonno'], (int) $USER->id);

        if (isset($mavzular[$k]) || isset($vazifalar[$k])) {
            lesson::save_lesson((int) $dars->id, $mavzular[$k] ?? '', $vazifalar[$k] ?? '', (int) $USER->id);
        }

        foreach ($students as $s) {
            $nom = $k . ':' . $s->id;
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
    $xabar = $xato ? "Saqlandi. {$xato} ta katak tushunarsiz bo'lgani uchun o'tkazib yuborildi." : 'Saqlandi';
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

echo $OUTPUT->header();

// Chorak tanlash
$options = [];
foreach ($terms as $i => $t) {
    $options[$i] = $t->name . ' (' . $t->startdate . ' – ' . $t->enddate . ')';
}
$select = new single_select(
    new moodle_url('/local/targetzenit/jurnal.php', ['courseid' => $courseid]),
    'term', $options, $termidx);
$select->set_label('Chorak: ');
echo html_writer::div($OUTPUT->render($select), 'mb-2');

echo html_writer::div(
    html_writer::tag('span', 'Katak: <b>bo\'sh</b> = keldi · <b>raqam</b> = baho (1–' . lesson::MAX_GRADE .
        ') · <b>k</b> = kelmadi · <b>s</b> = sababli · <b>kech</b> = kechikdi',
        ['class' => 'text-muted small']),
    'mb-3'
);

if (!$darslar) {
    echo $OUTPUT->notification('Bu chorakda bu kurs uchun dars topilmadi (jadvalda yo\'q yoki chorak bo\'sh).', 'info');
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
echo html_writer::tag('th', "O'quvchi", ['class' => 'tz-col-ism']);
foreach ($darslar as $l) {
    $sana = userdate(strtotime($l['date']), '%d.%m');
    $bu = ($l['key'] === $bugungi_kalit);
    echo html_writer::tag('th',
        html_writer::div($sana, 'tz-j-date') . html_writer::div($l['lessonno'] . '-d', 'tz-meta'),
        ['class' => 'tz-col-kun' . ($bu ? ' tz-bugun-col' : '')] + ($bu ? ['id' => 'tz-bugun-ustun'] : []));
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');

echo html_writer::start_tag('tbody');
$n = 0;
foreach ($students as $s) {
    $n++;
    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', $n, ['class' => 'tz-col-n']);
    echo html_writer::tag('td', fullname($s), ['class' => 'tz-col-ism']);
    foreach ($darslar as $l) {
        $dars = $darsyozuv[$l['key']] ?? null;
        $rec = ($dars && isset($kataklar[$s->id][$dars->id])) ? $kataklar[$s->id][$dars->id] : null;
        echo html_writer::tag('td',
            html_writer::empty_tag('input', [
                'type' => 'text',
                'name' => 'cell[' . $l['key'] . ':' . $s->id . ']',
                'value' => journal::cell_text($rec),
                'class' => 'tz-cell',
                'maxlength' => 4,
                'autocomplete' => 'off',
            ]),
            ['class' => 'tz-col-kun' . ($l['key'] === $bugungi_kalit ? ' tz-bugun-col' : '')]);
    }
    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');
echo html_writer::end_div();

// ---------------- O'NG: chorak mavzulari
echo html_writer::start_div('tz-jurnal-mavzu');
echo html_writer::tag('h5', 'Mavzular — ' . s($term->name));
echo html_writer::start_div('tz-mavzu-scroll', ['id' => 'tz-mavzu-scroll']);
echo html_writer::start_tag('table', ['class' => 'generaltable tz-mavzu']);
echo html_writer::tag('thead',
    html_writer::tag('tr',
        html_writer::tag('th', 'Sana') .
        html_writer::tag('th', 'Mavzu / uy vazifa')));
echo html_writer::start_tag('tbody');
foreach ($darslar as $i => $l) {
    $dars = $darsyozuv[$l['key']] ?? null;
    $sana = userdate(strtotime($l['date']), '%d.%m') . ' · ' . $l['lessonno'] . '-d';
    $bu = ($l['key'] === $bugungi_kalit);
    echo html_writer::start_tag('tr',
        ['class' => $bu ? 'tz-bugun-qator' : ''] + ($bu ? ['id' => 'tz-bugun-mavzu'] : []));
    echo html_writer::tag('td', html_writer::div(($i + 1) . '. ' . $sana, 'tz-meta'), ['class' => 'tz-m-sana']);
    echo html_writer::tag('td',
        html_writer::empty_tag('input', [
            'type' => 'text', 'name' => 'topic[' . $l['key'] . ']',
            'value' => $dars->topic ?? '', 'class' => 'form-control form-control-sm mb-1',
            'placeholder' => 'Mavzu',
        ]) .
        html_writer::empty_tag('input', [
            'type' => 'text', 'name' => 'homework[' . $l['key'] . ']',
            'value' => $dars->homework ?? '', 'class' => 'form-control form-control-sm tz-hw-input',
            'placeholder' => 'Uy vazifa',
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
echo html_writer::tag('h5', 'Chorak rejasini yuklash (Excel yoki CSV)');
echo html_writer::tag('p',
    'Ustunlar: <b>№ | Mavzu | Uy vazifa</b>. Sana yozilmaydi — mavzular shu chorakdagi ' .
    'darslarga ketma-ket joylashadi (hozir ' . count($darslar) . ' ta dars bor).',
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
        ' To\'ldirilgan mavzularning ustiga yozilsin (standart holatda tegilmaydi)'),
    'mb-2 small');
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Yuklash',
    'class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
