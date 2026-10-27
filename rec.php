<?php
// Dars yozuvi — ALOHIDA OYNA.
//
// NEGA ALOHIDA: yozuv dars sahifasining ichida turganda, o'qituvchi baho
// saqlasa yoki jurnalga o'tsa sahifa qayta yuklanadi va MediaRecorder o'ladi —
// yozuv uzilib qoladi. Bu oyna ochiq turgani uchun asosiy oynada istagan ishni
// qilish mumkin: yo'qlama, baho, chorak jurnali, sahifani yangilash.

require(__DIR__ . '/../../config.php');

require_login();   // avval kirish — baza so'rovi undan keyin

$lessonid = required_param('lessonid', PARAM_INT);

$dars = $DB->get_record('local_targetzenit_lesson', ['id' => $lessonid], '*', MUST_EXIST);
$course = get_course($dars->courseid);
require_login($course);

$context = context_course::instance($course->id);
require_capability('local/targetzenit:teach', $context);

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
