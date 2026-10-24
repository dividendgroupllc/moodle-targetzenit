<?php
// Dars kokpiti — o'qituvchining kundalik ish ekrani.
// Bitta sahifada: mavzu, uy vazifa, yo'qlama, baho va oldingi baholar jurnali.

require(__DIR__ . '/../../config.php');

use local_targetzenit\lesson;
use local_targetzenit\timetable;

$courseid = required_param('courseid', PARAM_INT);
$date = optional_param('date', date('Y-m-d'), PARAM_TEXT);
$lessonno = optional_param('lessonno', 0, PARAM_INT);

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

// Dars raqami berilmagan bo'lsa (jadvaldan emas, to'g'ridan-to'g'ri kirilgan)
// uni JADVALDAN aniqlaymiz. Aks holda hamma narsa "1-dars" ostiga yozilib,
// chorak jurnalidagi to'g'ri ustunda ko'rinmay qolardi.
if ($lessonno < 1) {
    $dow = (int) date('N', strtotime($date));
    $jadval = $DB->get_fieldset_select('local_targetzenit_timetable', 'lessonno',
        'courseidnumber = :c AND dayno = :d',
        ['c' => $course->idnumber, 'd' => $dow]);
    sort($jadval);
    $lessonno = $jadval ? (int) $jadval[0] : 1;
}

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
// Yozuv ALOHIDA OYNADA ochiladi: shu sahifada baho saqlanganda yoki jurnalga
// o'tilganda sahifa qayta yuklanadi va yozuv uzilib qolardi.
if (get_config('local_targetzenit', 'audioenabled')) {
    $yozuv = \local_targetzenit\audio::get((int) $dars->id);
    $kun = (int) (get_config('local_targetzenit', 'audioretentiondays') ?: 15);
    $recurl = new moodle_url('/local/targetzenit/rec.php', ['lessonid' => $dars->id]);

    echo html_writer::start_div('card mb-3 tz-audio-card');
    echo html_writer::start_div('card-body');

    if ($yozuv && $yozuv->status === 'recording') {
        echo html_writer::tag('div', '● Yozuv ochiq — alohida oynada davom etmoqda.',
            ['class' => 'text-danger font-weight-bold mb-2']);
    } else if ($yozuv && in_array($yozuv->status, \local_targetzenit\audio::FAYLLI, true)) {
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
        if ($yozuv->status === 'transcribed') {
            $qoldi = max(0, $kun - (int) floor((time() - $yozuv->timecreated) / DAYSECS));
            echo html_writer::tag('div',
                "Tahlil qilingan. Ovoz yana <b>{$qoldi} kun</b> saqlanadi — tahlil " .
                "qanchalik to'g'ri chiqqanini asl yozuv bilan solishtirish uchun.",
                ['class' => 'text-muted small mb-2']);
        }
    } else if ($yozuv && $yozuv->status === 'deleted') {
        echo html_writer::tag('div', "🎙 Audio muddati o'tgani uchun o'chirilgan (tahlil matni qoldi).",
            ['class' => 'text-muted mb-2']);
    }

    $fayl_bor = $yozuv && in_array($yozuv->status, \local_targetzenit\audio::FAYLLI, true);
    echo html_writer::tag('button',
        $fayl_bor ? '🎙 Qaytadan yozish' : '🎙 Darsni yozib olish',
        ['type' => 'button', 'id' => 'tz-rec-open',
         'class' => $fayl_bor ? 'btn btn-outline-secondary' : 'btn btn-danger btn-lg',
         'data-url' => $recurl->out(false)]);

    echo html_writer::tag('div',
        "Yozuv <b>alohida oynada</b> ochiladi — u ochiq turganda bu sahifada " .
        "yo'qlama qilish, baho qo'yish va jurnalga o'tish mumkin, yozuv uzilmaydi. " .
        "Audio {$kun} kun saqlanadi.",
        ['class' => 'text-muted small mt-2']);

    echo html_writer::end_div();
    echo html_writer::end_div();

    $PAGE->requires->js_amd_inline("
        require([], function() {
            var b = document.getElementById('tz-rec-open');
            if (!b) { return; }
            b.addEventListener('click', function() {
                window.open(b.getAttribute('data-url'), 'tzrec',
                    'width=420,height=460,menubar=no,toolbar=no,location=no');
            });
        });
    ");
}

// ---------------------------------------------------------- AI DARS TAHLILI
$tahlil = \local_targetzenit\ai::for_lesson((int) $dars->id);
if ($tahlil) {
    $d = $tahlil['data'];
    echo html_writer::start_div('card mb-3 tz-ai-card');
    echo html_writer::start_div('card-body');

    // --- Umumiy foiz
    $um = $d['overall']['score'] ?? null;
    $sarlavha = '🤖 ' . get_string('ai_analyse', 'local_targetzenit');
    if ($um !== null) {
        $rang = $um >= 75 ? 'success' : ($um >= 55 ? 'warning' : 'danger');
        $sarlavha .= ' ' . html_writer::tag('span', $um . '%',
            ['class' => 'badge badge-' . $rang . ' tz-ai-umumiy']);
    }
    echo html_writer::tag('h5', $sarlavha);

    if (!empty($d['summary'])) {
        echo html_writer::tag('p', s($d['summary']), ['class' => 'mb-3']);
    }

    // --- Mavzuga muvofiqlik
    if (!empty($d['topic_match'])) {
        $tm = $d['topic_match'];
        $ok = !empty($tm['covered']);
        echo html_writer::div(
            html_writer::tag('span', $ok ? "✔ Mavzu o'tildi" : "✖ Mavzu o'tilmadi",
                ['class' => 'badge ' . ($ok ? 'badge-success' : 'badge-danger')]) . ' ' .
            (!empty($tm['actual_topic'])
                ? html_writer::tag('span', 'Darsda: ' . s($tm['actual_topic']), ['class' => 'ml-2'])
                : '') .
            (!empty($tm['note']) ? html_writer::div(s($tm['note']), 'text-muted small mt-1') : ''),
            'mb-3'
        );
    }

    // --- Kategoriyalar (ICALT) — foizli chiziqlar
    if (!empty($d['categories']) && is_array($d['categories'])) {
        echo html_writer::tag('div',
            "Baholash ramkasi: <b>ICALT</b> (xalqaro dars kuzatuvi) + Jahon banki <b>TEACH</b>",
            ['class' => 'text-muted small mb-2']);

        foreach ($d['categories'] as $c) {
            $ball = (int) ($c['score'] ?? 0);
            $ishonch = $c['confidence'] ?? 'medium';
            $past = ($ishonch === 'low');
            $rang = $past ? 'secondary' : ($ball >= 75 ? 'success' : ($ball >= 55 ? 'warning' : 'danger'));

            echo html_writer::start_div('tz-cat');
            echo html_writer::div(
                html_writer::tag('span', s($c['name'] ?? $c['code'] ?? ''), ['class' => 'tz-cat-nom']) .
                html_writer::tag('span', ($c['weight'] ?? 0) . '%', ['class' => 'tz-cat-vazn']) .
                html_writer::tag('span', $past ? 'audio orqali baholanmadi' : $ball . '%',
                    ['class' => 'tz-cat-ball text-' . $rang]),
                'tz-cat-bosh');
            echo html_writer::div(
                html_writer::div('', 'tz-bar-ichi bg-' . $rang,
                    ['style' => 'width:' . ($past ? 0 : $ball) . '%']),
                'tz-bar');
            if (!empty($c['actual_topic'])) {
                echo html_writer::div('Darsda: ' . s($c['actual_topic']), 'tz-cat-dalil');
            }
            if (!empty($c['evidence'])) {
                echo html_writer::div(s($c['evidence']), 'tz-cat-dalil');
            }
            if (!empty($c['advice'])) {
                echo html_writer::div('→ ' . s($c['advice']), 'tz-cat-tavsiya');
            }
            echo html_writer::end_div();
        }

        if (!empty($d['overall']['chetda'])) {
            echo html_writer::div(
                "Hisobga olinmadi (audio orqali baholab bo'lmaydi): " .
                s(implode(', ', $d['overall']['chetda'])) .
                ". Umumiy foiz qolgan kategoriyalar bo'yicha hisoblangan.",
                'text-muted small mt-2');
        }
    } else if (!empty($d['structure']) && is_array($d['structure'])) {
        // Eski ko'rinishdagi tahlil (kategoriyalar joriy etilgunga qadar)
        $t = new html_table();
        $t->head = ['Bosqich', "Bo'ldimi", 'Daqiqa', 'Izoh'];
        $t->attributes['class'] = 'generaltable tz-ai-struct';
        foreach ($d['structure'] as $b) {
            $bor = !empty($b['present']);
            $t->data[] = [
                s($b['stage'] ?? ''),
                html_writer::tag('span', $bor ? 'ha' : "yo'q",
                    ['class' => $bor ? 'text-success' : 'text-danger']),
                (int) ($b['minutes'] ?? 0),
                html_writer::tag('span', s($b['note'] ?? ''), ['class' => 'small text-muted']),
            ];
        }
        echo html_writer::table($t);
    }

    // --- O'lchovlar (audio orqali ishonchli o'lchanadiganlar)
    $m = $d['metrics'] ?? $d['speech'] ?? [];
    if ($m) {
        $q = [];
        if (isset($m['teacher_talk_pct'])) {
            $q[] = "O'qituvchi " . (int) $m['teacher_talk_pct'] . '% · o\'quvchilar '
                . (int) ($m['student_talk_pct'] ?? 0) . '%';
        }
        if (isset($m['questions_total'])) {
            $q[] = 'Savollar: ' . (int) $m['questions_total'] .
                (isset($m['open_questions']) ? ' (ochiq: ' . (int) $m['open_questions'] . ')' : '');
        }
        if (isset($m['wait_time_avg_sec'])) {
            $q[] = "Kutish vaqti: " . (int) $m['wait_time_avg_sec'] . ' s';
        }
        if (isset($m['students_addressed'])) {
            $q[] = 'Murojaat qilingan o\'quvchi: ' . (int) $m['students_addressed'];
        }
        if (isset($m['on_task_pct'])) {
            $q[] = "O'quv ishida: " . (int) $m['on_task_pct'] . '%';
        }
        if (!empty($m['language']) && is_array($m['language'])) {
            $til = [];
            foreach ($m['language'] as $k => $v) {
                $til[] = strtoupper(s($k)) . ' ' . (int) $v . '%';
            }
            $q[] = 'Til: ' . implode(' · ', $til);
        }
        if ($q) {
            echo html_writer::div(implode('  |  ', $q), 'tz-ai-speech mt-3');
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
            ['class' => 'mt-3']);
    }

    // --- Transkript
    if (!empty($tahlil['audio']->transcript)) {
        echo html_writer::start_tag('details', ['class' => 'mt-3']);
        echo html_writer::tag('summary', "To'liq matn (transkript)", ['class' => 'tz-ai-summary']);
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
        echo html_writer::start_div('card mb-3 tz-ai-card');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('div',
            !empty($a->aierror)
                ? 'Tahlil qilinmadi: ' . s($a->aierror)
                : get_string('ai_pending', 'local_targetzenit') .
                  " — yozuv tugagach avtomatik boshlanadi (~1 daqiqa).",
            ['class' => !empty($a->aierror) ? 'text-danger mb-2' : 'text-muted mb-2']);

        echo html_writer::tag('button', '🤖 Hozir tahlil qilish',
            ['type' => 'button', 'id' => 'tz-ai-now', 'class' => 'btn btn-primary']);
        echo html_writer::tag('span', '', ['id' => 'tz-ai-holat', 'class' => 'ml-3 font-weight-bold']);
        echo html_writer::end_div();
        echo html_writer::end_div();

        $PAGE->requires->js_amd_inline("
            require([], function() {
                var b = document.getElementById('tz-ai-now');
                var h = document.getElementById('tz-ai-holat');
                if (!b) { return; }
                var baza = '" . (new moodle_url('/local/targetzenit/ajax.php'))->out(false) . "' +
                    '?lessonid=" . (int) $dars->id . "&sesskey=" . sesskey() . "';

                // Uzun so'rov nginx tomonidan uzilsa ham PHP tahlilni tugatadi —
                // shuning uchun xatoda ham holatni so'rab turamiz.
                var kuzat = function (qoldi) {
                    if (qoldi <= 0) {
                        h.textContent = 'Hali tugamadi — sahifani keyinroq yangilang.';
                        b.disabled = false;
                        return;
                    }
                    fetch(baza + '&action=status', {method: 'POST'})
                        .then(function (r) { return r.json(); })
                        .then(function (j) {
                            if (j.analysed) { window.location.reload(); return; }
                            if (j.aierror) {
                                h.textContent = 'Xato: ' + j.aierror;
                                b.disabled = false;
                                return;
                            }
                            setTimeout(function () { kuzat(qoldi - 1); }, 5000);
                        })
                        .catch(function () { setTimeout(function () { kuzat(qoldi - 1); }, 5000); });
                };

                b.addEventListener('click', function () {
                    b.disabled = true;
                    h.textContent = 'Tahlil qilinmoqda, bu bir necha daqiqa olishi mumkin...';
                    fetch(baza + '&action=analyse', {method: 'POST'})
                        .then(function (r) { return r.json(); })
                        .then(function (j) {
                            if (j.ok) { window.location.reload(); }
                            else { h.textContent = 'Xato: ' + (j.error || 'noma\'lum'); b.disabled = false; }
                        })
                        .catch(function () {
                            h.textContent = 'Fonda davom etmoqda, kutilmoqda...';
                            kuzat(36);     // 3 daqiqagacha kuzatamiz
                        });
                });
            });
        ");
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
