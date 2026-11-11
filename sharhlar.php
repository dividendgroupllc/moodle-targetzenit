<?php
// O'QUVCHI SHARHLARI — DIREKTOR LENTASI (faqat o'qish).
//
// Sahifa javob beradigan savol: «kim qaysi o'quvchiga nima deb yozdi va
// o'quvchi uni o'qidimi?». Shuning uchun lenta VAQT bo'yicha teskari
// tartibda (yangisi yuqorida) va har qatorda to'rt o'q: qachon · kim ·
// qaysi guruh/fan · kim haqida.
//
// FAQAT O'QISH — direktor sharh yozmaydi. Sabab `yozuvlar.php` bilan bir
// xil: kuzatuvchi o'zi baho qo'yadigan dalilni yarata olmasligi kerak.
// Direktorning o'qituvchiga aytadigan gapi uchun FIDBEK bor.
//
// Matn Moodle'ning standart `{comments}` jadvalida — `classes/sharh.php`.

require(__DIR__ . '/../../config.php');

use local_targetzenit\dashboard;
use local_targetzenit\output\shell;
use local_targetzenit\output\ui;
use local_targetzenit\sharh;

require_login();

$context = context_system::instance();
require_capability('local/targetzenit:viewteachers', $context);

$muallif = optional_param('muallif', 0, PARAM_INT);
$guruh = trim(optional_param('guruh', '', PARAM_TEXT));
$oquvchi = trim(optional_param('oquvchi', '', PARAM_TEXT));
$from = optional_param('from', '', PARAM_TEXT);
$to = optional_param('to', '', PARAM_TEXT);
$korilmagan = optional_param('korilmagan', 0, PARAM_INT);
$open = optional_param('open', 0, PARAM_INT);
$ochir = optional_param('ochir', 0, PARAM_INT);

// Standart oyna — oxirgi 30 kun (`yozuvlar.php` lentasi bilan bir xil):
// butun o'quv yilini bir sahifada ko'rsatish lentani foydasiz uzaytiradi.
$sana = fn($v, $zaxira) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? $v : $zaxira;
$from = $sana($from, date('Y-m-d', strtotime('-30 days')));
$to = $sana($to, date('Y-m-d'));

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/sharhlar.php'));
shell::setup($PAGE, get_string('sh_navall', 'local_targetzenit'));

$url = function(array $p = []) use ($muallif, $guruh, $oquvchi, $from, $to, $korilmagan) {
    $asos = ['from' => $from, 'to' => $to];
    if ($muallif) {
        $asos['muallif'] = $muallif;
    }
    if ($guruh !== '') {
        $asos['guruh'] = $guruh;
    }
    if ($oquvchi !== '') {
        $asos['oquvchi'] = $oquvchi;
    }
    if ($korilmagan) {
        $asos['korilmagan'] = 1;
    }
    return new moodle_url('/local/targetzenit/sharhlar.php', $p + $asos);
};

// ------------------------------------------------------------- O'CHIRISH
//
// Direktor sharhni O'CHIRA OLMAYDI (`viewteachers` yetarli emas) — u
// kuzatuvchi. Faqat SAYT MA'MURI, sinov ma'lumotini tozalash uchun.
// Tekshiruv `sharh::ochir()` ichida.
if ($ochir && confirm_sesskey()) {
    $n = sharh::ochir($ochir);
    redirect($url(), get_string('sh_deleted', 'local_targetzenit', $n), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

shell::start('sharhlar', get_string('sh_navall', 'local_targetzenit'),
    get_string('sh_allhint', 'local_targetzenit'));

echo html_writer::start_div('tz-sh');

$filtrlar = sharh::filtrlar();

// ================================================================= FILTRLAR
// Ko'rinish `yozuvlar.php` dagi lenta filtrlari bilan AYNI BIR XIL
// (`tz-dir-filters` / `tz-dir-f`) — direktor sahifadan sahifaga o'tganda
// filtr qatori joyini o'zgartirmasligi kerak.
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'tz-dir-filters',
    'action' => (new moodle_url('/local/targetzenit/sharhlar.php'))->out(false)]);

echo html_writer::div(
    html_writer::tag('label', get_string('sh_teacher', 'local_targetzenit'))
    . html_writer::select([0 => get_string('sh_allteachers', 'local_targetzenit')]
        + $filtrlar['muallif'], 'muallif', $muallif, false), 'tz-dir-f');

echo html_writer::div(
    html_writer::tag('label', get_string('sh_group', 'local_targetzenit'))
    . html_writer::select(['' => get_string('sh_allgroups', 'local_targetzenit')]
        + $filtrlar['guruh'], 'guruh', $guruh, false), 'tz-dir-f');

// Ism VA familiya bo'yicha — qaysi tartibda yozilganidan qat'i nazar
// topilsin (so'rovda ikki maydon alohida tekshiriladi).
echo html_writer::div(
    html_writer::tag('label', get_string('sh_student', 'local_targetzenit'))
    . html_writer::empty_tag('input', ['type' => 'search', 'name' => 'oquvchi',
        'value' => $oquvchi,
        'placeholder' => get_string('sh_searchph', 'local_targetzenit')]), 'tz-dir-f');

echo html_writer::div(
    html_writer::tag('label', get_string('rec_from', 'local_targetzenit'))
    . html_writer::empty_tag('input', ['type' => 'date', 'name' => 'from', 'value' => $from]),
    'tz-dir-f');

echo html_writer::div(
    html_writer::tag('label', get_string('rec_to', 'local_targetzenit'))
    . html_writer::empty_tag('input', ['type' => 'date', 'name' => 'to', 'value' => $to]),
    'tz-dir-f');

// «O'quvchi o'qimagan» — hisobot emas, AMAL ro'yxati: javobsiz qolgan sharhlar.
echo html_writer::div(
    html_writer::tag('label', '&nbsp;')
    . html_writer::tag('label',
        html_writer::checkbox('korilmagan', 1, (bool) $korilmagan, '') . ' '
        . get_string('sh_onlyunseen', 'local_targetzenit'),
        ['class' => 'tz-sh-check']), 'tz-dir-f');

echo html_writer::div(
    html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary btn-sm',
        'value' => get_string('rec_apply', 'local_targetzenit')])
    . ' ' . html_writer::link(new moodle_url('/local/targetzenit/sharhlar.php'),
        get_string('rec_reset', 'local_targetzenit'), ['class' => 'btn btn-sm btn-link']),
    'tz-dir-f');

echo html_writer::end_tag('form');

// =================================================================== LENTA
$rows = sharh::royxat([
    'muallif' => $muallif,
    'guruh' => $guruh,
    'oquvchi' => $oquvchi,
    'from' => $from,
    'to' => $to,
    'korilmagan' => $korilmagan,
]);

// TO'PLAMLARGA GURUHLASH — bitta sharh = besh kategoriya = besh qator.
// Guruhlamasak lenta besh barobar uzun bo'lib ko'rinardi va «bir o'quvchiga
// beshta sharh yozilgan» degan noto'g'ri manzara chiqardi.
$tplar = sharh::guruhla($rows);

// Plitalar lentaning O'ZIDAN hisoblanadi — alohida so'rov yozilsa u filtrdan
// uzilib qoladi va «jami 120, ro'yxatda 12» degan chalkashlik chiqadi.
$maxfiy = 0;
$okilmagan = 0;
$oquvchilar = [];
$mualliflar = [];
foreach ($tplar as $tp) {
    $b = $tp['bosh'];
    if ($b->visibility === 'maxfiy') {
        $maxfiy++;
    } else if ($b->status !== 'seen') {
        $okilmagan++;
    }
    $oquvchilar[(int) $b->studentid] = true;
    $mualliflar[(int) $b->authorid] = true;
}

echo html_writer::div(
    ui::tile((string) count($tplar), get_string('sh_total', 'local_targetzenit'))
    . ui::tile((string) count($mualliflar), get_string('sh_teachers', 'local_targetzenit'))
    . ui::tile((string) count($oquvchilar), get_string('sh_covered', 'local_targetzenit'),
        get_string('sh_coveredhint', 'local_targetzenit'))
    . ui::tile((string) $okilmagan, get_string('sh_unseen', 'local_targetzenit'),
        get_string('sh_unseenhint', 'local_targetzenit'),
        '', $okilmagan ? 'warning' : '')
    . ui::tile((string) $maxfiy, get_string('sh_privates', 'local_targetzenit'),
        get_string('sh_privateshint', 'local_targetzenit')),
    'tz-dir-hero');

if (!$rows) {
    echo $OUTPUT->notification(get_string('sh_emptyall', 'local_targetzenit'), 'info');
    echo html_writer::end_div();
    shell::end();
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_div('tz-dir-card');
echo html_writer::start_div('tz-dir-tablewrap');
echo html_writer::start_tag('table', ['class' => 'tz-dir-table tz-sh-all']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
foreach (['sh_when', 'sh_teacher', 'sh_groupsubject', 'sh_student',
          'sh_text', 'sh_state'] as $k) {
    echo html_writer::tag('th', get_string($k, 'local_targetzenit'));
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

$yorliqlar = sharh::kategoriyalar_menu();

foreach ($tplar as $tp) {
    $b = $tp['bosh'];
    $ochiq = ($open === (int) $b->id);

    echo html_writer::start_tag('tr');

    echo html_writer::tag('td',
        html_writer::div(userdate($b->timecreated, '%d.%m.%Y'), '')
        . html_writer::div(userdate($b->timecreated, '%H:%M'), 'tz-dir-sub'));

    // O'qituvchi ismi uning panelining o'ziga olib boradi — «bu odam
    // umuman qanday ishlayapti?» savoli shu yerda tug'iladi.
    echo html_writer::tag('td', html_writer::link(
        new moodle_url('/local/targetzenit/oqituvchi.php', ['userid' => (int) $b->authorid]),
        s(dashboard::ism($b->afirstname ?? '', $b->alastname ?? ''))));

    echo html_writer::tag('td', html_writer::tag('span',
        s(sharh::kurs_yorlig($b->courseidnumber ?? '', $b->coursename ?? '')),
        ['class' => 'tz-dir-chip']));

    echo html_writer::tag('td', html_writer::link(
        new moodle_url('/local/targetzenit/baholarim.php', ['userid' => (int) $b->studentid]),
        s(dashboard::ism($b->sfirstname ?? '', $b->slastname ?? ''))));

    // MATN KATAGI.
    //
    // Yopiq holatda: nechta kategoriya to'ldirilgani + birinchisining
    // boshi. Ochiq holatda: BESHTASI YORLIG'I BILAN — direktorning asosiy
    // savoli «kim qaysi o'quvchiga nima deb yozdi», ya'ni kategoriya
    // nomisiz matn foydasiz.
    //
    // `<details>` emas, chunki qator tanlovi havolada saqlanishi kerak
    // (filtrni yo'qotmasdan boshqa qatorga o'tish).
    if ($ochiq) {
        $matn = '';
        foreach ($yorliqlar as $kat => $nom) {
            if (!isset($tp['matnlar'][$kat])) {
                continue;
            }
            $matn .= html_writer::div(
                html_writer::div(s($nom), 'tz-sh-katlab')
                . html_writer::tag('p', nl2br(s(sharh::matn($tp['matnlar'][$kat]))),
                    ['class' => 'tz-sh-body']),
                'tz-sh-kat');
        }
        // Eski erkin sharh ham ko'rinadi — ko'chirilmaydi, yorlig'i bilan
        if (isset($tp['matnlar'][sharh::ESKI_KAT])) {
            $matn .= html_writer::div(
                html_writer::div(get_string('sh_kat_umumiy', 'local_targetzenit'),
                    'tz-sh-katlab')
                . html_writer::tag('p',
                    nl2br(s(sharh::matn($tp['matnlar'][sharh::ESKI_KAT]))),
                    ['class' => 'tz-sh-body']),
                'tz-sh-kat tz-sh-kat-old');
        }
        if (sharh::ochirish_mumkinmi()) {
            $matn .= html_writer::div(html_writer::link(
                $url(['open' => 0, 'ochir' => (int) $b->id, 'sesskey' => sesskey()]),
                get_string('sh_delete', 'local_targetzenit'),
                ['class' => 'btn btn-sm btn-outline-danger',
                 'onclick' => 'return confirm('
                     . json_encode(get_string('sh_delete_q', 'local_targetzenit')) . ')']),
                'tz-sh-del');
        }
        $ichi = html_writer::div($matn, 'tz-sh-fulltext');
    } else {
        $birinchi = reset($tp['matnlar']);
        $xom = $birinchi ? sharh::matn($birinchi) : '';
        $qisqa = core_text::substr(trim($xom), 0, sharh::QISQA);
        $ichi = html_writer::div(
            html_writer::tag('span',
                get_string('sh_katcount', 'local_targetzenit',
                    count($tp['matnlar'])),
                ['class' => 'tz-sh-katchip'])
            . ' ' . s($qisqa)
            . (core_text::strlen($xom) > sharh::QISQA ? '…' : ''),
            'tz-sh-ctext');
    }

    echo html_writer::tag('td',
        $ichi
        . html_writer::link($url(['open' => $ochiq ? 0 : (int) $b->id]),
            $ochiq ? get_string('sh_close', 'local_targetzenit')
                   : get_string('sh_openfull', 'local_targetzenit'),
            ['class' => 'tz-dir-sub']),
        ['class' => 'tz-sh-textcell']);

    if ($b->visibility === 'maxfiy') {
        $holat = html_writer::tag('span', get_string('sh_private', 'local_targetzenit'),
            ['class' => 'tz-dir-chip tz-dir-warning']);
    } else if ($b->status === 'seen') {
        $holat = html_writer::tag('span', get_string('sh_seen', 'local_targetzenit'),
            ['class' => 'tz-dir-chip tz-dir-good'])
            . ($b->seenat ? html_writer::div(userdate($b->seenat, '%d.%m.%Y'),
                'tz-dir-sub') : '');
    } else {
        $holat = html_writer::tag('span', get_string('sh_notseen', 'local_targetzenit'),
            ['class' => 'tz-dir-chip']);
    }
    echo html_writer::tag('td', $holat);

    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');
echo html_writer::end_div();   // .tz-dir-tablewrap
echo html_writer::end_div();   // .tz-dir-card

echo html_writer::end_div();   // .tz-sh
shell::end();
echo $OUTPUT->footer();
