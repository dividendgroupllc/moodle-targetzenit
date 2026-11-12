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
echo html_writer::label(get_string('rc_mic', 'local_targetzenit'),
    'tz-audio-device', true, ['class' => 'small font-weight-bold']);
echo html_writer::tag('select', '',
    ['id' => 'tz-audio-device', 'class' => 'form-control form-control-sm']);
echo html_writer::end_div();

// ============ TUGALLANMAGAN YOZUV BO'LSA — TANLOV BERAMIZ
//
// NEGA (dars 201, 2026-11-12): tizim "sahifam qayta yuklandi, davom et" va
// "noto'g'ri boshladim, boshidan yozaman" holatlarini ajrata olmaydi —
// ikkisida ham tugallanmagan bo'laklar qoladi. Avval kod HAR DOIM davom
// ettirardi, natijada 33 soniyalik noto'g'ri boshlangan parcha 20 daqiqalik
// darsning oldiga ulanib, faylda "ikki xil ovoz" paydo bo'lgan.
// Qarorni O'QITUVCHI qabul qiladi.
$qoldiq = ($yozuv && $yozuv->status === 'recording')
    ? count(\local_targetzenit\audio::parts($lessonid)) : 0;
$qoldiq_sek = $qoldiq * \local_targetzenit\audio::SLICE_SEC;

if ($qoldiq) {
    echo html_writer::tag('div',
        get_string('rc_resume', 'local_targetzenit',
            (object) ['soni' => $qoldiq, 'vaqt' => format_time($qoldiq_sek)]),
        ['class' => 'alert alert-warning small mb-2']);

    echo html_writer::tag('button',
        '▶ ' . get_string('rc_continue', 'local_targetzenit', format_time($qoldiq_sek)),
        ['type' => 'button', 'id' => 'tz-audio-start', 'data-rejim' => 'davom',
         'class' => 'btn btn-danger btn-lg btn-block']);
    echo html_writer::tag('button',
        '🔄 ' . get_string('rc_fresh', 'local_targetzenit'),
        ['type' => 'button', 'id' => 'tz-audio-yangi',
         'class' => 'btn btn-outline-danger btn-block mt-2']);
} else {
    echo html_writer::tag('button', '🎙 Yozishni boshlash',
        ['type' => 'button', 'id' => 'tz-audio-start', 'data-rejim' => 'davom',
         'class' => 'btn btn-danger btn-lg btn-block']);
}
echo html_writer::tag('button', '⏸ Pauza',
    ['type' => 'button', 'id' => 'tz-audio-pause', 'class' => 'btn btn-warning btn-block hidden mt-2']);
echo html_writer::tag('button', get_string('rc_stop', 'local_targetzenit'),
    ['type' => 'button', 'id' => 'tz-audio-stop', 'class' => 'btn btn-dark btn-lg btn-block hidden mt-2']);

echo html_writer::tag('div', '', ['id' => 'tz-audio-holat', 'class' => 'tz-audio-holat my-3']);

echo html_writer::tag('div',
    get_string('rc_hint', 'local_targetzenit', $kun),
    ['class' => 'text-muted small']);

if ($yozuv && in_array($yozuv->status, \local_targetzenit\audio::FAYLLI, true)) {
    echo html_writer::tag('div',
        get_string('rc_exists', 'local_targetzenit',
            number_format($yozuv->filesize / 1048576, 1)),
        ['class' => 'alert alert-info mt-3 small']);
}

echo html_writer::end_div();

// Keshni sindirish uchun versiya: aks holda o'qituvchining brauzeri
// eski `recorder.js` ni ishlatib yuradi va tuzatish yetib bormaydi.
$PAGE->requires->js(new moodle_url('/local/targetzenit/js/recorder.js',
    ['v' => get_config('local_targetzenit', 'version')]));
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
