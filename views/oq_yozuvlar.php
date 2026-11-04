<?php
// «Dars yozuvlari» ko'rinishi — oqituvchi.php ichidan chaqiriladi.
//
// Direktordagi `yozuvlar.php` lentasining AYNI O'ZI (`output\lenta`), bitta
// farq bilan: o'qituvchi filtri YO'Q va qamrov serverda qotib yozilgan.
//
// MAXFIYLIK — eng muhim joy:
//   `teacher` filtri $userid dan keladi, so'rov parametridan EMAS. $userid
//   esa oqituvchi.php da `teacher::korish_mumkinmi()` bilan tekshirilgan:
//   o'zi bo'lsa — ochiq, boshqa odam bo'lsa `viewteachers` kerak. Ya'ni
//   oddiy o'qituvchi URL ni qo'lda o'zgartirib ham boshqa odamning yozuvini
//   ko'ra olmaydi — u sahifaning o'ziga kira olmaydi.
//   Audio faylning o'zi ham himoyalangan: `lib.php` dagi `pluginfile` shu
//   kursda `teach` yoki tizim darajasida `viewanalysis` talab qiladi.
defined('MOODLE_INTERNAL') || die();

// DIQQAT: `use` ifodalari FAYLGA XOS va `require` orqali O'TMAYDI.
use local_targetzenit\dashboard;
use local_targetzenit\output\lenta;
use local_targetzenit\teacher;

$guruhlar = teacher::record_groups($userid, $from, $to);
// Tanlangan guruh ro'yxatda bo'lmasa — bekor qilinadi. Qo'lda yozilgan
// `gk` qiymati so'rovga tushib qolmasligi kerak.
if ($guruh !== '' && !isset($guruhlar[$guruh])) {
    $guruh = '';
}

$natija = dashboard::recordings([
    // QOTIB YOZILGAN: so'rov parametri emas.
    'teacher' => $userid,
    'groupkey' => $guruh,
    'from' => $from,
    'to' => $to,
], $page, lenta::QATOR);

// ====================================================== SARLAVHA
echo html_writer::start_div('tz-dir-head');
echo html_writer::div(
    html_writer::tag('h4', get_string('recordings', 'local_targetzenit'),
        ['class' => 'tz-dir-cardtitle'])
    . html_writer::div(get_string($ozim ? 'oq_rec_sub' : 'rec_sub', 'local_targetzenit',
        $natija['jami']), 'tz-dir-sub'));
echo html_writer::end_div();

// ====================================================== FILTRLAR
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'tz-dir-filters',
    'action' => (new moodle_url('/local/targetzenit/oqituvchi.php'))->out(false)]);

echo html_writer::empty_tag('input',
    ['type' => 'hidden', 'name' => 'view', 'value' => 'yozuvlar']);
if (!$ozim) {
    echo html_writer::empty_tag('input',
        ['type' => 'hidden', 'name' => 'userid', 'value' => $userid]);
}

// O'QITUVCHI FILTRI ATAYLAB YO'Q — bu lenta bitta odamniki.
if ($guruhlar) {
    echo html_writer::div(
        html_writer::tag('label', get_string('dir_colgroup', 'local_targetzenit'))
        . html_writer::select(['' => get_string('rec_allgroups', 'local_targetzenit')]
            + $guruhlar, 'guruh', $guruh, false), 'tz-dir-f');
}

echo html_writer::div(
    html_writer::tag('label', get_string('rec_from', 'local_targetzenit'))
    . html_writer::empty_tag('input', ['type' => 'date', 'name' => 'from', 'value' => $from]),
    'tz-dir-f');

echo html_writer::div(
    html_writer::tag('label', get_string('rec_to', 'local_targetzenit'))
    . html_writer::empty_tag('input', ['type' => 'date', 'name' => 'to', 'value' => $to]),
    'tz-dir-f');

echo html_writer::div(
    html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary btn-sm',
        'value' => get_string('rec_apply', 'local_targetzenit')])
    // Tozalash — yangi havola quriladi, `$url()` emas: u joriy filtrlarni
    // o'zi qo'shib yuboradi va tugma hech narsa qilmagan bo'lardi.
    . ' ' . html_writer::link(
        new moodle_url('/local/targetzenit/oqituvchi.php',
            ['view' => 'yozuvlar'] + ($ozim ? [] : ['userid' => $userid])),
        get_string('rec_reset', 'local_targetzenit'), ['class' => 'btn btn-sm btn-link']),
    'tz-dir-f');

echo html_writer::end_tag('form');

// ====================================================== LENTA
echo lenta::render($natija['rows'], [
    'url' => $url,
    'page' => $page,
    // O'z lentasida ism ustuni ortiqcha — uning o'rniga sinf va fan turadi.
    'ism' => false,
    // O'chirish FAQAT sayt ma'murida (yozuvlar.php bilan bir xil qoida):
    // yozuv — o'qituvchi haqidagi xulosaning DALILI, u o'z dalilini yo'q
    // qila olmasligi kerak.
    'ochirish' => has_capability('moodle/site:config', $syscontext),
    'fidbek' => true,
]);

if ($natija['jami'] > lenta::QATOR) {
    echo $OUTPUT->paging_bar($natija['jami'], $page, lenta::QATOR,
        $url(['view' => 'yozuvlar']));
}

lenta::js($PAGE);
