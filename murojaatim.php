<?php
// MUROJAATIM — o'quvchi akkauntidagi ko'rinish. OTA-ONA shu sahifani o'qiydi:
// Moodle'da ota-ona akkaunti yo'q (faqat Student/Instructor/Employee
// sinxronlanadi), shuning uchun ota-ona bolasining logini bilan kiradi.
//
// NIMA KO'RINADI (2026-11-04 qarori): shikoyat matni VA rahbariyat javobi.
// Rahbarlarning ichki kommentlari ko'rinmaydi — `_murojaat_izoh.korinish`
// bo'yicha ajratiladi.
//
// Javobni ochganda `otaonakordi` qayd etiladi: rahbar "javob yetib bordimi"
// ni biladi va yetmagan bo'lsa menejer qo'ng'iroq qiladi.

require(__DIR__ . '/../../config.php');

use local_targetzenit\murojaat;

require_login();

$id = optional_param('id', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/murojaatim.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('mur_mine', 'local_targetzenit'));
$PAGE->set_heading(get_string('mur_mine', 'local_targetzenit'));

$rows = murojaat::meniki((int) $USER->id);

// Bildirishnomadagi havola bilan kelgan bo'lsa — o'sha murojaat ko'rilgan
// deb belgilanadi (faqat O'ZINING murojaati, metod ichida tekshiriladi).
if ($id) {
    foreach ($rows as $m) {
        if ((int) $m->id === $id) {
            murojaat::otaona_kordi($m, (int) $USER->id);
        }
    }
}

echo $OUTPUT->header();

$t = fn(string $k, $a = null) => get_string($k, 'local_targetzenit', $a);

if (!$rows) {
    echo $OUTPUT->notification($t('mur_emptymine'), 'info');
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_div('tz-mur tz-mur-parent');

foreach ($rows as $m) {
    $javobbor = (bool) $m->qarorvaqti;

    // Javobi kelganini ochiq ko'rsatamiz: ota-ona uzun ro'yxatni varaqlab
    // "javob bormi" deb qidirib yurmasligi kerak.
    $chip = html_writer::tag('span', s($m->turi), ['class' => 'tz-dir-qchip']);
    $chip .= html_writer::tag('span',
        $javobbor ? $t('mur_answer') : $t('mur_waiting'),
        ['class' => 'tz-dir-qchip ' . ($javobbor ? 'tz-dir-good' : 'tz-dir-warning')]);

    $ichi = html_writer::div(
        html_writer::div($t('mur_said'), 'tz-dir-lab')
        . html_writer::div(format_text($m->matn, FORMAT_PLAIN), 'tz-mur-quote'),
        'tz-mur-sec');

    if ($javobbor) {
        $ichi .= html_writer::div(
            html_writer::div($t('mur_answer') . ': ' . s((string) $m->qarorturi), 'tz-dir-lab')
            . html_writer::div(format_text((string) $m->qaror, FORMAT_PLAIN), 'tz-mur-yechim')
            . html_writer::div(userdate($m->qarorvaqti, get_string('strftimedatetimeshort')),
                'tz-dir-sub'),
            'tz-mur-sec');
    } else {
        $ichi .= html_writer::div($t('mur_waiting'), 'tz-mur-ogoh');
    }

    echo html_writer::tag('details',
        html_writer::tag('summary',
            html_writer::div(userdate($m->ochilgan, get_string('strftimedateshort'))
                . ' · ' . s($m->kategoriya ?: $t('mur_nocat')), 'tz-dir-sub')
            . html_writer::div(s(shorten_text($m->matn, 120)), 'tz-mur-qisqa')
            . html_writer::div($chip, 'tz-dir-chips'),
            ['class' => 'tz-mur-sum'])
        . $ichi,
        ['class' => 'tz-mur-blok'] + (($id === (int) $m->id || $javobbor) ? ['open' => 'open'] : []));
}

echo html_writer::end_div();
echo $OUTPUT->footer();
