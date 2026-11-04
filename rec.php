<?php
// Dars yozuvi — ALOHIDA OYNA.
//
// NEGA ALOHIDA: yozuv dars sahifasining ichida turganda, o'qituvchi baho
// saqlasa yoki jurnalga o'tsa sahifa qayta yuklanadi va MediaRecorder o'ladi —
// yozuv uzilib qoladi. Bu oyna ochiq turgani uchun asosiy oynada istagan ishni
// qilish mumkin: yo'qlama, baho, chorak jurnali, sahifani yangilash.

require(__DIR__ . '/../../config.php');

require_login();   // avval kirish — baza so'rovi undan keyin

// Ikki xil kirish yo'li:
//   lessonid=N                      — dars sahifasidan (jurnal yozuvi bor)
//   courseid=N&date=..&lessonno=N   — jadvalning "bugun" ro'yxatidan
//
// NEGA IKKINCHISI KERAK: jadvalda hali ochilmagan darsning `_lesson` yozuvi
// yo'q, ya'ni `lessonid` ham yo'q. Avval dars sahifasiga kirib, keyin yozuvni
// ochish kerak edi — dars boshlanayotgan paytda ikki bosqich. Endi jadvaldagi
// tugma to'g'ridan-to'g'ri shu yerga olib keladi va yozuv o'sha zahoti
// boshlanadi. Jurnal yozuvi `get_or_create()` bilan shu yerda tug'iladi —
// yaratish mantig'i YAGONA joyda qoladi.
$lessonid = optional_param('lessonid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$date = optional_param('date', '', PARAM_TEXT);
$lessonno = optional_param('lessonno', 0, PARAM_INT);

if ($lessonid) {
    $dars = $DB->get_record('local_targetzenit_lesson', ['id' => $lessonid], '*', MUST_EXIST);
    $course = get_course($dars->courseid);
    require_login($course);
    $context = context_course::instance($course->id);
    require_capability('local/targetzenit:teach', $context);
} else {
    if (!$courseid || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $lessonno < 1) {
        throw new moodle_exception('invalidparameter', 'debug');
    }
    $course = get_course($courseid);
    require_login($course);
    $context = context_course::instance($course->id);
    // RUXSAT AVVAL: `get_or_create()` baza yozuvi yaratadi, ya'ni tekshiruvdan
    // oldin chaqirilsa begona odam ham qator tug'dira olardi.
    require_capability('local/targetzenit:teach', $context);

    $dars = \local_targetzenit\lesson::get_or_create($courseid, $date, $lessonno,
        (int) $USER->id);
    $lessonid = (int) $dars->id;
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/rec.php', ['lessonid' => $lessonid]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('audio_record', 'local_targetzenit'));

$yozuv = \local_targetzenit\audio::get($lessonid);
$kun = (int) (get_config('local_targetzenit', 'audioretentiondays') ?: 15);

echo $OUTPUT->header();

echo html_writer::start_div('tz-rec-popup');

echo html_writer::tag('div',
    format_string($course->fullname) . '<br>' .
    userdate(strtotime($dars->lessondate), '%d-%B') . ' · ' . $dars->lessonno . '-dars',
    ['class' => 'tz-rec-bosh mb-3']);

// Mikrofon tanlash — tashqi mikrofon ulanganda kerak bo'ladi
echo html_writer::start_div('form-group hidden mb-3');
echo html_writer::label('Mikrofon:', 'tz-audio-device', true, ['class' => 'small font-weight-bold']);
echo html_writer::tag('select', '',
    ['id' => 'tz-audio-device', 'class' => 'form-control form-control-sm']);
echo html_writer::end_div();

echo html_writer::tag('button', '🎙 Yozishni boshlash',
    ['type' => 'button', 'id' => 'tz-audio-start', 'class' => 'btn btn-danger btn-lg btn-block']);
echo html_writer::tag('button', '⏸ Pauza',
    ['type' => 'button', 'id' => 'tz-audio-pause', 'class' => 'btn btn-warning btn-block hidden mt-2']);
echo html_writer::tag('button', '■ To\'xtatish va saqlash',
    ['type' => 'button', 'id' => 'tz-audio-stop', 'class' => 'btn btn-dark btn-lg btn-block hidden mt-2']);

echo html_writer::tag('div', '', ['id' => 'tz-audio-holat', 'class' => 'tz-audio-holat my-3']);

echo html_writer::tag('div',
    "<b>Bu oynani yopmang.</b> U ochiq turganda asosiy oynada yo'qlama qilish, " .
    "baho qo'yish va jurnalni ochish mumkin — yozuv davom etaveradi.<br><br>" .
    "<b>Internet uzilsa yozuv TO'XTAMAYDI</b> — bo'laklar navbatda turadi va " .
    "aloqa qaytishi bilan o'zi yuboriladi, ovoz yo'qolmaydi.<br><br>" .
    "<b>Pauza</b> 30 soniyadan keyin o'zi davom etadi — unutib qo'yilsa ham " .
    "dars yozilmay qolmaydi.<br><br>" .
    "Audio {$kun} kun saqlanadi.",
    ['class' => 'text-muted small']);

if ($yozuv && in_array($yozuv->status, \local_targetzenit\audio::FAYLLI, true)) {
    echo html_writer::tag('div',
        '✔ Bu darsda yozuv bor (' . number_format($yozuv->filesize / 1048576, 1) . ' MB). ' .
        'Qaytadan yozsangiz eskisi almashadi.',
        ['class' => 'alert alert-info mt-3 small']);
}

echo html_writer::end_div();

$PAGE->requires->js(new moodle_url('/local/targetzenit/js/recorder.js'));
$PAGE->requires->js_amd_inline("
    require([], function() {
        var boshla = function() {
            if (window.tzAudioInit) {
                window.tzAudioInit({
                    ajaxurl: '" . (new moodle_url('/local/targetzenit/ajax.php'))->out(false) . "',
                    lessonid: " . (int) $lessonid . ",
                    sesskey: '" . sesskey() . "',
                    popup: true
                });
            } else { setTimeout(boshla, 150); }
        };
        boshla();
    });
");

echo $OUTPUT->footer();
