<?php
// Dars kokpiti — o'qituvchining kundalik ish ekrani.
// Bitta sahifada: mavzu, uy vazifa, yo'qlama, baho va oldingi baholar jurnali.

require(__DIR__ . '/../../config.php');

use local_targetzenit\lesson;
use local_targetzenit\timetable;

$courseid = required_param('courseid', PARAM_INT);
$date = optional_param('date', date('Y-m-d'), PARAM_TEXT);
$lessonno = optional_param('lessonno', 1, PARAM_INT);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($course->id);
require_capability('local/targetzenit:teach', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/dars.php',
    ['courseid' => $courseid, 'date' => $date, 'lessonno' => $lessonno]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

$dars = lesson::get_or_create($courseid, $date, $lessonno, (int) $USER->id);
$students = lesson::students($context);

// ------------------------------------------------------------------ SAQLASH
if (optional_param('saqla', 0, PARAM_INT) && confirm_sesskey()) {
    $topic = optional_param('topic', '', PARAM_TEXT);
    $homework = optional_param('homework', '', PARAM_TEXT);
    lesson::save_lesson((int) $dars->id, $topic, $homework, (int) $USER->id);

    $att = optional_param_array('att', [], PARAM_ALPHA);
    $grades = optional_param_array('grade', [], PARAM_RAW_TRIMMED);
    $comments = optional_param_array('comment', [], PARAM_TEXT);

    foreach ($students as $s) {
        lesson::save_record(
            (int) $dars->id,
            (int) $s->id,
            $att[$s->id] ?? 'present',
            $grades[$s->id] ?? null,
            $comments[$s->id] ?? ''
        );
    }
    redirect($PAGE->url, 'Saqlandi', null, \core\output\notification::NOTIFY_SUCCESS);
}

$yozuvlar = lesson::records((int) $dars->id);

echo $OUTPUT->header();

// ------------------------------------------------------------------ SARLAVHA
$sana_matni = userdate(strtotime($date), '%A, %d-%B %Y');
echo html_writer::div(
    html_writer::tag('span', $sana_matni, ['class' => 'tz-dars-sana']) . ' · ' .
    html_writer::tag('span', $lessonno . '-dars', ['class' => 'tz-dars-raqam']),
    'tz-dars-bosh mb-2'
);

// Jadvalga qaytish havolasi
echo html_writer::div(
    html_writer::link(new moodle_url('/local/targetzenit/index.php', ['view' => 'today', 'date' => $date]),
        '← ' . get_string('timetable', 'local_targetzenit'),
        ['class' => 'btn btn-sm btn-outline-secondary mr-2']) .
    html_writer::link(new moodle_url('/local/targetzenit/jurnal.php', ['courseid' => $courseid]),
        'Chorak jurnali', ['class' => 'btn btn-sm btn-outline-primary']),
    'mb-3'
);

// ------------------------------------------------------- DARS AUDIO YOZUVI
// Panel FORMADAN TASHQARIDA: tugmalar forma ichida bo'lsa uni yuborib yuborardi.
if (get_config('local_targetzenit', 'audioenabled')) {
    $yozuv = \local_targetzenit\audio::get((int) $dars->id);
    $kun = (int) (get_config('local_targetzenit', 'audioretentiondays') ?: 15);

    echo html_writer::start_div('card mb-3 tz-audio-card');
    echo html_writer::start_div('card-body');

    if ($yozuv && $yozuv->status === 'ready') {
        $url = \local_targetzenit\audio::file_url((int) $dars->id, $context);
        echo html_writer::tag('div',
            '🎙 ' . get_string('audio_ready', 'local_targetzenit') . ' — ' .
            gmdate('H:i:s', (int) $yozuv->duration) . ', ' .
            number_format($yozuv->filesize / 1048576, 1) . ' MB',
            ['class' => 'font-weight-bold mb-2']);
        if ($url) {
            echo html_writer::tag('audio', '', ['src' => $url->out(false), 'controls' => 'controls',
                'preload' => 'none', 'class' => 'w-100 mb-2']);
        }
        echo html_writer::tag('div',
            "Audio {$kun} kundan keyin avtomatik o'chadi (tahlil matni saqlanib qoladi).",
            ['class' => 'text-muted small']);
        echo html_writer::tag('button', get_string('audio_record', 'local_targetzenit') . ' (qaytadan)',
            ['type' => 'button', 'id' => 'tz-audio-start', 'class' => 'btn btn-outline-secondary btn-sm mt-2']);
        echo html_writer::tag('button', get_string('audio_stop', 'local_targetzenit'),
            ['type' => 'button', 'id' => 'tz-audio-stop', 'class' => 'btn btn-danger btn-sm mt-2 hidden']);
    } else if ($yozuv && $yozuv->status === 'deleted') {
        echo html_writer::tag('div', "🎙 Audio muddati o'tgani uchun o'chirilgan.",
            ['class' => 'text-muted mb-2']);
        echo html_writer::tag('button', get_string('audio_record', 'local_targetzenit'),
            ['type' => 'button', 'id' => 'tz-audio-start', 'class' => 'btn btn-outline-secondary btn-sm']);
        echo html_writer::tag('button', get_string('audio_stop', 'local_targetzenit'),
            ['type' => 'button', 'id' => 'tz-audio-stop', 'class' => 'btn btn-danger btn-sm hidden']);
    } else {
        echo html_writer::tag('button',
            '🎙 ' . get_string('audio_record', 'local_targetzenit'),
            ['type' => 'button', 'id' => 'tz-audio-start', 'class' => 'btn btn-danger btn-lg']);
        echo html_writer::tag('button',
            '■ ' . get_string('audio_stop', 'local_targetzenit'),
            ['type' => 'button', 'id' => 'tz-audio-stop', 'class' => 'btn btn-dark btn-lg hidden']);
        echo html_writer::tag('div',
            "Dars boshida bosing. Yozuv har 15 soniyada serverga yuboriladi — " .
            "brauzer yopilsa ham yozilgani saqlanib qoladi. Audio {$kun} kundan keyin o'chadi.",
            ['class' => 'text-muted small mt-2']);
    }

    echo html_writer::tag('div', '', ['id' => 'tz-audio-holat', 'class' => 'tz-audio-holat mt-2']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    $PAGE->requires->js(new moodle_url('/local/targetzenit/js/recorder.js'));
    $PAGE->requires->js_amd_inline("
        require([], function() {
            var boshla = function() {
                if (window.tzAudioInit) {
                    window.tzAudioInit({
                        ajaxurl: '" . (new moodle_url('/local/targetzenit/ajax.php'))->out(false) . "',
                        lessonid: " . (int) $dars->id . ",
                        sesskey: '" . sesskey() . "'
                    });
                } else {
                    setTimeout(boshla, 200);
                }
            };
            boshla();
        });
    ");
}

// ---------------------------------------------------------- AI DARS TAHLILI
$tahlil = \local_targetzenit\ai::for_lesson((int) $dars->id);
if ($tahlil) {
    $d = $tahlil['data'];
    echo html_writer::start_div('card mb-3 tz-ai-card');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', '🤖 ' . get_string('ai_analyse', 'local_targetzenit'));

    // --- Xulosa
    if (!empty($d['summary'])) {
        echo html_writer::tag('p', s($d['summary']), ['class' => 'mb-3']);
    }

    // --- Mavzuga muvofiqlik
    if (!empty($d['topic_match'])) {
        $tm = $d['topic_match'];
        $ok = !empty($tm['covered']);
        echo html_writer::div(
            html_writer::tag('span', $ok ? '✔ Mavzu o\'tildi' : '✖ Mavzu o\'tilmadi',
                ['class' => 'badge ' . ($ok ? 'badge-success' : 'badge-danger')]) . ' ' .
            (!empty($tm['actual_topic'])
                ? html_writer::tag('span', 'Darsda: ' . s($tm['actual_topic']), ['class' => 'ml-2'])
                : '') .
            (!empty($tm['note']) ? html_writer::div(s($tm['note']), 'text-muted small mt-1') : ''),
            'mb-3'
        );
    }

    // --- Dars tuzilishi
    if (!empty($d['structure']) && is_array($d['structure'])) {
        $t = new html_table();
        $t->head = ['Bosqich', 'Bo\'ldimi', 'Daqiqa', 'Izoh'];
        $t->attributes['class'] = 'generaltable tz-ai-struct';
        foreach ($d['structure'] as $b) {
            $bor = !empty($b['present']);
            $t->data[] = [
                s($b['stage'] ?? ''),
                html_writer::tag('span', $bor ? 'ha' : 'yo\'q',
                    ['class' => $bor ? 'text-success' : 'text-danger']),
                (int) ($b['minutes'] ?? 0),
                html_writer::tag('span', s($b['note'] ?? ''), ['class' => 'small text-muted']),
            ];
        }
        echo html_writer::table($t);
    }

    // --- Nutq ulushi va til
    if (!empty($d['speech'])) {
        $sp = $d['speech'];
        $qator = [];
        if (isset($sp['teacher_pct'])) {
            $qator[] = "O'qituvchi " . (int) $sp['teacher_pct'] . '% · o'
                . "'quvchilar " . (int) ($sp['student_pct'] ?? 0) . '%';
        }
        if (isset($sp['questions_asked'])) {
            $qator[] = 'Savollar: ' . (int) $sp['questions_asked'] .
                ', javob olingan: ' . (int) ($sp['answers_received'] ?? 0);
        }
        if (!empty($sp['language']) && is_array($sp['language'])) {
            $til = [];
            foreach ($sp['language'] as $k => $v) {
                $til[] = strtoupper(s($k)) . ' ' . (int) $v . '%';
            }
            $qator[] = 'Til: ' . implode(' · ', $til);
        }
        if ($qator) {
            echo html_writer::div(implode('  |  ', $qator), 'tz-ai-speech mb-2');
        }
    }

    // --- Tavsiyalar
    if (!empty($d['recommendations']) && is_array($d['recommendations'])) {
        $li = '';
        foreach ($d['recommendations'] as $r) {
            $li .= html_writer::tag('li', s($r));
        }
        echo html_writer::tag('div',
            html_writer::tag('b', 'Tavsiyalar:') . html_writer::tag('ul', $li),
            ['class' => 'mt-2']);
    }

    // --- Transkript (yig'iladigan)
    if (!empty($tahlil['audio']->transcript)) {
        echo html_writer::start_tag('details', ['class' => 'mt-3']);
        echo html_writer::tag('summary', 'To\'liq matn (transkript)', ['class' => 'tz-ai-summary']);
        echo html_writer::tag('pre', s($tahlil['audio']->transcript), ['class' => 'tz-transcript']);
        echo html_writer::end_tag('details');
    }

    echo html_writer::div('Model: ' . s($tahlil['audio']->aimodel ?? '') . ' · ' .
        userdate($tahlil['audio']->analysedat), 'text-muted small mt-2');

    echo html_writer::end_div();
    echo html_writer::end_div();
} else if (\local_targetzenit\ai::enabled()) {
    $a = \local_targetzenit\audio::get((int) $dars->id);
    if ($a && $a->status === 'ready') {
        echo $OUTPUT->notification(
            !empty($a->aierror)
                ? 'Tahlil qilinmadi: ' . s($a->aierror) . ' — keyingi yurishda qayta urinadi.'
                : get_string('ai_pending', 'local_targetzenit') . ' — tahlil har 15 daqiqada ishlaydi.',
            !empty($a->aierror) ? 'warn' : 'info');
    }
}

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'saqla', 'value' => 1]);

// ------------------------------------------------------- MAVZU VA UY VAZIFA
echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-body');

echo html_writer::label(get_string('topic', 'local_targetzenit'), 'tz-topic', true, ['class' => 'font-weight-bold']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'id' => 'tz-topic', 'name' => 'topic',
    'value' => $dars->topic, 'class' => 'form-control mb-3',
    'placeholder' => 'Bugungi dars mavzusi',
]);

echo html_writer::label(get_string('homework', 'local_targetzenit'), 'tz-hw', true, ['class' => 'font-weight-bold']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'id' => 'tz-hw', 'name' => 'homework',
    'value' => $dars->homework, 'class' => 'form-control',
    'placeholder' => 'Keyingi darsga uy vazifa',
]);

echo html_writer::end_div();
echo html_writer::end_div();

// ------------------------------------------------- O'QUVCHILAR: YO'QLAMA+BAHO
if (!$students) {
    echo $OUTPUT->notification('Bu kursga o\'quvchilar yozilmagan.', 'warn');
} else {
    $t = new html_table();
    $t->head = ['#', get_string('student', 'local_targetzenit'),
        get_string('attendance', 'local_targetzenit'),
        get_string('grade', 'local_targetzenit') . ' (1–' . lesson::MAX_GRADE . ')',
        get_string('comment', 'local_targetzenit')];
    $t->attributes['class'] = 'generaltable tz-dars';
    $t->colclasses = ['tz-n', 'tz-ism', 'tz-att', 'tz-baho', 'tz-izoh'];

    $n = 0;
    foreach ($students as $s) {
        $n++;
        $y = $yozuvlar[$s->id] ?? null;
        $joriy = $y->attendance ?? 'present';

        $tugmalar = '';
        foreach (lesson::ATTEND as $kalit => $nom) {
            $id = "att-{$s->id}-{$kalit}";
            $tugmalar .= html_writer::empty_tag('input', [
                    'type' => 'radio', 'name' => "att[{$s->id}]", 'value' => $kalit,
                    'id' => $id, 'class' => 'tz-radio',
                ] + ($joriy === $kalit ? ['checked' => 'checked'] : []));
            $tugmalar .= html_writer::tag('label', $nom,
                ['for' => $id, 'class' => 'tz-att-label tz-att-' . $kalit]);
        }

        $t->data[] = [
            $n,
            fullname($s),
            html_writer::div($tugmalar, 'tz-att-group'),
            html_writer::empty_tag('input', [
                'type' => 'number', 'name' => "grade[{$s->id}]",
                'value' => ($y && $y->grade !== null) ? $y->grade : '',
                'min' => 1, 'max' => lesson::MAX_GRADE, 'step' => 1,
                'class' => 'form-control tz-grade-input',
            ]),
            html_writer::empty_tag('input', [
                'type' => 'text', 'name' => "comment[{$s->id}]",
                'value' => $y->comment ?? '', 'class' => 'form-control',
                'maxlength' => 255,
            ]),
        ];
    }
    echo html_writer::div(html_writer::table($t), 'tz-scroll');
}

echo html_writer::div(
    html_writer::empty_tag('input', [
        'type' => 'submit', 'value' => get_string('savechanges'),
        'class' => 'btn btn-primary btn-lg',
    ]),
    'my-3'
);
echo html_writer::end_tag('form');

// ------------------------------------------------------------------ JURNAL
$jurnal = lesson::journal($courseid, 12);
if ($jurnal['lessons'] && $students) {
    echo html_writer::tag('h4', get_string('journal', 'local_targetzenit'), ['class' => 'mt-4']);

    $t = new html_table();
    $head = [get_string('student', 'local_targetzenit')];
    foreach ($jurnal['lessons'] as $l) {
        $head[] = html_writer::div(userdate(strtotime($l->lessondate), '%d.%m'), 'tz-j-date') .
            html_writer::div($l->lessonno . '-d', 'tz-meta');
    }
    $head[] = get_string('average', 'local_targetzenit');
    $t->head = $head;
    $t->attributes['class'] = 'generaltable tz-journal';

    foreach ($students as $s) {
        $qator = [fullname($s)];
        $uniki = $jurnal['cells'][$s->id] ?? [];
        foreach ($jurnal['lessons'] as $l) {
            $r = $uniki[$l->id] ?? null;
            if (!$r) {
                $qator[] = '';
            } else if ($r->grade !== null) {
                $qator[] = html_writer::tag('strong', $r->grade,
                    ['class' => 'tz-g tz-g-' . ($r->grade >= 7 ? 'ok' : ($r->grade >= 5 ? 'mid' : 'low'))]);
            } else {
                $qator[] = html_writer::tag('span', lesson::ATTEND_SHORT[$r->attendance] ?? '',
                    ['class' => 'tz-meta']);
            }
        }
        $ort = lesson::average($uniki);
        $qator[] = $ort !== null ? html_writer::tag('strong', $ort) : '';
        $t->data[] = $qator;
    }
    echo html_writer::div(html_writer::table($t), 'tz-scroll');
}

echo $OUTPUT->footer();
