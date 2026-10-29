<?php
// DARS KARTASI — direktor/zavuch uchun FAQAT O'QISH rejimida.
//
// NEGA ALOHIDA SAHIFA: dars kokpiti (`dars.php`) kurs kontekstida `teach`
// ruxsatini talab qiladi va u yerda baho/yo'qlama TAHRIR qilinadi. Direktorga
// `teach` ataylab BERILMAGAN — u baho qo'ymasligi kerak. Shu sahifa esa
// tizim darajasidagi `viewanalysis` bilan ishlaydi va hech narsani
// o'zgartirmaydi.
//
// Tahlil kartasining o'zi `classes/output/tahlil.php` da — o'qituvchi
// ko'rayotgani bilan AYNI BIR KOD.

require(__DIR__ . '/../../config.php');

use local_targetzenit\ai;
use local_targetzenit\audio;
use local_targetzenit\output\tahlil;

require_login();

$syscontext = context_system::instance();
require_capability('local/targetzenit:viewanalysis', $syscontext);

$lessonid = optional_param('lessonid', 0, PARAM_INT);
$slotid = optional_param('slotid', 0, PARAM_INT);

// Slotdan kelgan bo'lsa (direktor panelidan) — darsni topamiz
if (!$lessonid && $slotid) {
    $f = $DB->get_record('local_targetzenit_fact', ['slotid' => $slotid], 'lessonid');
    $lessonid = (int) ($f->lessonid ?? 0);
    if (!$lessonid) {
        $slot = $DB->get_record('local_targetzenit_slot', ['id' => $slotid], '*', MUST_EXIST);
        $PAGE->set_context($syscontext);
        $PAGE->set_url(new moodle_url('/local/targetzenit/dars_tahlil.php', ['slotid' => $slotid]));
        $PAGE->set_title(get_string('dir_lessoncard', 'local_targetzenit'));
        $PAGE->set_heading(get_string('dir_lessoncard', 'local_targetzenit'));
        echo $OUTPUT->header();
        echo html_writer::div(
            html_writer::tag('b', format_string($slot->subjectname) . ' · '
                . format_string($slot->groupname)) . ' · '
            . userdate(strtotime($slot->lessondate), '%d.%m.%Y') . ' · '
            . $slot->lessonno . '-dars', 'mb-2');
        echo $OUTPUT->notification(get_string('dir_nojournal', 'local_targetzenit'), 'info');
        echo $OUTPUT->footer();
        exit;
    }
}

$dars = $DB->get_record('local_targetzenit_lesson', ['id' => $lessonid], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $dars->courseid], 'id, fullname, idnumber', MUST_EXIST);
$context = context_course::instance($dars->courseid);

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/targetzenit/dars_tahlil.php', ['lessonid' => $lessonid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('dir_lessoncard', 'local_targetzenit'));
$PAGE->set_heading(get_string('dir_lessoncard', 'local_targetzenit'));

// Jurnal holati — yo'qlama va baholar yig'indisi
$yoz = $DB->get_record_sql("
    SELECT COUNT(1) AS soni,
           COUNT(grade) AS baholar,
           SUM(grade) AS ballyigindi,
           SUM(CASE WHEN attendance = 'absent' THEN 1 ELSE 0 END) AS yoq,
           SUM(CASE WHEN attendance = 'late' THEN 1 ELSE 0 END) AS kech,
           SUM(CASE WHEN attendance = 'excused' THEN 1 ELSE 0 END) AS sababli
      FROM {local_targetzenit_record}
     WHERE lessonid = :l", ['l' => $lessonid]);

$oqituvchi = $dars->teacherid
    ? $DB->get_record('user', ['id' => $dars->teacherid], 'id, firstname, lastname')
    : null;

echo $OUTPUT->header();
echo html_writer::start_div('tz-dir');

// =================================================== DARS SARLAVHASI
$sarlavha = html_writer::tag('b', format_string($course->fullname))
    . html_writer::tag('span',
        ' · ' . userdate(strtotime($dars->lessondate), '%d.%m.%Y')
        . ' · ' . $dars->lessonno . '-dars',
        ['class' => 'tz-dir-meta']);
if ($oqituvchi) {
    $sarlavha .= html_writer::div(
        get_string('dir_wroteby', 'local_targetzenit', fullname($oqituvchi)), 'tz-dir-sub');
}
echo html_writer::div($sarlavha, 'tz-dir-head');

// Dars kokpitiga havola — FAQAT shu kursda dars o'tish huquqi borlarga.
// Direktorda bu ruxsat yo'q, shuning uchun unda tugma ham ko'rinmaydi.
if (has_capability('local/targetzenit:teach', $context)) {
    echo html_writer::div(html_writer::link(
        new moodle_url('/local/targetzenit/dars.php', [
            'courseid' => $dars->courseid,
            'date' => $dars->lessondate,
            'lessonno' => $dars->lessonno,
        ]), get_string('dir_opencockpit', 'local_targetzenit'),
        ['class' => 'btn btn-sm btn-outline-secondary']), 'mb-3');
}

// ================================================== JURNAL HOLATI
echo html_writer::start_div('tz-dir-card');
echo html_writer::tag('h4', get_string('dir_journalstate', 'local_targetzenit'),
    ['class' => 'tz-dir-cardtitle']);

$mavzu = trim((string) $dars->topic);
$vazifa = trim((string) $dars->homework);

echo html_writer::div(
    html_writer::tag('span', $mavzu !== ''
        ? get_string('dir_topic', 'local_targetzenit') : get_string('dir_notopic', 'local_targetzenit'),
        ['class' => 'tz-dir-chip tz-dir-' . ($mavzu !== '' ? 'good' : 'critical')])
    . ' ' . ($mavzu !== '' ? s($mavzu) : ''), 'mb-2');

echo html_writer::div(
    html_writer::tag('span', $vazifa !== ''
        ? get_string('dir_hw', 'local_targetzenit') : get_string('dir_nohw', 'local_targetzenit'),
        ['class' => 'tz-dir-chip tz-dir-' . ($vazifa !== '' ? 'good' : 'warning')])
    . ' ' . ($vazifa !== '' ? s($vazifa) : ''), 'mb-2');

$soni = (int) ($yoz->soni ?? 0);
if (!$soni) {
    echo html_writer::div(
        html_writer::tag('span', get_string('dir_noatt', 'local_targetzenit'),
            ['class' => 'tz-dir-chip tz-dir-critical']), 'mb-2');
} else {
    $kelgan = $soni - (int) $yoz->yoq - (int) $yoz->sababli;
    $qism = [get_string('dir_attn', 'local_targetzenit', (object) [
        'kelgan' => $kelgan, 'jami' => $soni])];
    if ((int) $yoz->yoq) {
        $qism[] = "kelmadi: " . (int) $yoz->yoq;
    }
    if ((int) $yoz->sababli) {
        $qism[] = "sababli: " . (int) $yoz->sababli;
    }
    if ((int) $yoz->kech) {
        $qism[] = "kechikdi: " . (int) $yoz->kech;
    }
    $baholar = (int) $yoz->baholar;
    if ($baholar) {
        $qism[] = "baho: {$baholar} ta · o'rtacha "
            . round(((float) $yoz->ballyigindi) / $baholar, 1);
    } else {
        $qism[] = get_string('dir_nograde', 'local_targetzenit');
    }
    echo html_writer::div(
        html_writer::tag('span', get_string('dir_attdone', 'local_targetzenit'),
            ['class' => 'tz-dir-chip tz-dir-good'])
        . ' ' . s(implode(' · ', $qism)), 'mb-2');
}
echo html_writer::end_div();

// ======================================================== AUDIO
$a = audio::get($lessonid);
if ($a) {
    echo html_writer::start_div('tz-dir-card');
    echo html_writer::tag('h4', get_string('dir_recording', 'local_targetzenit'),
        ['class' => 'tz-dir-cardtitle']);

    $url = audio::file_url($lessonid, $context);
    if ($url) {
        echo html_writer::div(
            '<audio controls preload="none" style="width:100%;max-width:520px">'
            . '<source src="' . $url->out(false) . '">'
            . '</audio>', 'mb-1');
        echo html_writer::div(
            ($a->duration ? gmdate('H:i:s', (int) $a->duration) . ' · ' : '')
            . ($a->filesize ? number_format($a->filesize / 1048576, 1) . ' MB' : ''),
            'tz-dir-sub');
    } else if ($a->status === 'deleted') {
        // Audio 15 kundan keyin o'chadi, transkript va tahlil qoladi
        echo html_writer::div(get_string('dir_audiogone', 'local_targetzenit'), 'tz-dir-sub');
    } else if ($a->status === 'recording') {
        echo html_writer::div(get_string('dir_audiorec', 'local_targetzenit'), 'tz-dir-sub');
    } else {
        echo html_writer::div(get_string('dir_audionone', 'local_targetzenit'), 'tz-dir-sub');
    }
    echo html_writer::end_div();
}

// ======================================================= AI TAHLIL
// Xuddi o'qituvchi ko'radigan karta — ayni bir kod. Amal tugmalari
// ko'rsatiladi: direktorda `viewanalysis` bor, ya'ni qayta tahlil qilishi
// mumkin (ajax.php da ham shu ruxsat tekshiriladi).
$karta = tahlil::render($dars, true);
if ($karta === '') {
    echo html_writer::div(
        ai::enabled()
            ? get_string('dir_analysisnone', 'local_targetzenit')
            : get_string('dir_aioff', 'local_targetzenit'),
        'tz-dir-card tz-dir-sub');
} else {
    echo $karta;
}

echo html_writer::end_div();
echo $OUTPUT->footer();
