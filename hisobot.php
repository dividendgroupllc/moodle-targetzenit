<?php
// OTA-ONA UCHUN O'QUVCHI HISOBOTI — chop etiladigan varaq (PDF).
//
// Ikki rejim:
//   ?userid=<id>              — bitta o'quvchi (ota-ona, tutor, o'qituvchi)
//   ?guruh=<guruh>&hammasi=1  — butun sinf ketma-ket (majlis uchun 30 varaq)
//
// NEGA ALOHIDA SAHIFA, `baholarim.php` NING ICHIDA EMAS: bu sahifaning
// yagona vazifasi — QOG'OZ. Unda menyu, yon panel, tugmalar yo'q (chop
// etishda hammasi `display:none`), o'lchov A4 ga qotirilgan. Ekran uchun
// qilingan sahifani bosmaga moslash har doim ikkalasini ham buzadi.
//
// PDF QANDAY OLINADI: brauzerning o'zi (Chrome -> Chop etish -> «Save as
// PDF»). Serverda PDF yasash (TCPDF) ataylab TANLANMADI: TCPDF o'zbek
// tipografiyasi, SVG diagramma va matn oqimi bilan yomon ishlaydi, har
// o'zgarishda shrift yuklash kerak bo'ladi. Brauzer esa aynan shu HTML'ni
// bosadi — ekranda ko'rgan narsa qog'ozga tushadi.

require(__DIR__ . '/../../config.php');

use local_targetzenit\hisobot;
use local_targetzenit\output\hisobot_varaq;
use local_targetzenit\timetable;

require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT);
$guruh = trim(optional_param('guruh', '', PARAM_TEXT));
$hammasi = optional_param('hammasi', 0, PARAM_INT);
$termidx = optional_param('term', -1, PARAM_INT);

$syscontext = context_system::instance();
$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/targetzenit/hisobot.php', ['userid' => $userid]));
// `embedded` — Moodle sarlavhasi, menyusi va futeri qog'ozga tushmasligi
// uchun. Qolgan sahifalar bilan bir xil qobiq.
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('hr_title', 'local_targetzenit'));

$year = timetable::current_year();
$terms = $year ? array_values(timetable::terms($year)) : [];

echo $OUTPUT->header();

if (!$terms) {
    echo $OUTPUT->notification(get_string('mg_noterms', 'local_targetzenit'), 'warn');
    echo $OUTPUT->footer();
    exit;
}

// Standart chorak — bugun qaysi chorakda bo'lsa o'sha. Chorak tugagan
// bo'lsa (majlis kanikulda bo'ladi) oxirgi TUGAGAN chorak olinadi: bo'sh
// varaq chiqarishdan ko'ra yaqinda yopilgan chorak foydaliroq.
if ($termidx < 0 || !isset($terms[$termidx])) {
    $bugun = date('Y-m-d');
    $termidx = 0;
    $topildi = false;
    foreach ($terms as $i => $t) {
        if ($bugun >= $t->startdate && $bugun <= $t->enddate) {
            $termidx = $i;
            $topildi = true;
            break;
        }
    }
    if (!$topildi) {
        foreach ($terms as $i => $t) {
            if ($t->enddate < $bugun) {
                $termidx = $i;
            }
        }
    }
}
$term = $terms[$termidx];

// ============================================================= SINF TANLASH
// `?hammasi=1` guruhsiz kelsa — bu MENYUDAN kirish. Sinf rahbari o'z
// sinfini, direktor/zavuch esa istalganini tanlaydi.
//
// NEGA KERAK: to'plam chop etish havolasi faqat `sharh.php` dagi kurs
// tanlovidan chiqardi, ya'ni sinf rahbari o'z sinfining PDF'ini qayerdan
// olishni bilmasdi. Menyu havolasi parametrsiz bo'lishi kerak.
if ($hammasi && $guruh === '') {
    $hammasiguruh = hisobot::guruhlar($year);
    $mumkin = [];
    foreach ($hammasiguruh as $g) {
        if (hisobot::guruh_mumkinmi($year, $g)) {
            $mumkin[] = $g;
        }
    }

    echo html_writer::start_div('tz-hr-pick');
    echo html_writer::tag('h3', get_string('hr_pickgroup', 'local_targetzenit'));
    if (!$mumkin) {
        echo $OUTPUT->notification(get_string('hr_noaccess', 'local_targetzenit'), 'info');
    } else {
        echo html_writer::div(get_string('hr_pickhint', 'local_targetzenit'),
            'tz-hr-toolhint mb-3');
        foreach ($mumkin as $g) {
            echo html_writer::link(
                new moodle_url('/local/targetzenit/hisobot.php',
                    ['guruh' => $g, 'hammasi' => 1, 'term' => $termidx]),
                s($g), ['class' => 'tz-hr-btn tz-hr-btn2 tz-hr-pickitem']);
        }
    }
    echo html_writer::end_div();
    echo $OUTPUT->footer();
    exit;
}

// =========================================================== KIMNI CHIQARAMIZ
$oquvchilar = [];

if ($hammasi && $guruh !== '') {
    if (!hisobot::guruh_mumkinmi($year, $guruh)) {
        throw new moodle_exception('nopermissions', 'error', '',
            get_string('hr_noaccess', 'local_targetzenit'));
    }
    $oquvchilar = array_keys(hisobot::guruh_oquvchilari($year, $guruh));
} else {
    if (!hisobot::korish_mumkinmi($userid)) {
        throw new moodle_exception('nopermissions', 'error', '',
            get_string('hr_noaccess', 'local_targetzenit'));
    }
    $oquvchilar = [$userid];
}

// ======================================================================= @page
// NEGA SHU YERDA, `styles.css` DA EMAS: `@page` ni selektor bilan chegaralab
// bo'lmaydi, ya'ni uni umumiy stil faylga yozsak BUTUN SAYTNING bosma
// chetlari o'zgarardi (jurnal, hisobot, Moodle sahifalari — hammasi).
// Qoida bitta sahifaga tegishli bo'lsa, u shu sahifada turishi kerak.
echo '<style>@media print { @page { size: A4 portrait; margin: 11mm 10mm 12mm; } }</style>';

// ==================================================================== VOSITALAR
// Chop etishda butun blok yashiriladi (`tz-hr-tools` -> `display:none`).
$vositalar = '';

$tanlov = [];
foreach ($terms as $i => $t) {
    $tanlov[$i] = $t->name;
}
$asos = $hammasi && $guruh !== ''
    ? ['guruh' => $guruh, 'hammasi' => 1]
    : ['userid' => $userid];
$sel = new single_select(
    new moodle_url('/local/targetzenit/hisobot.php', $asos), 'term', $tanlov, $termidx);
$sel->set_label(get_string('mg_term', 'local_targetzenit') . ': ');
$vositalar .= html_writer::div($OUTPUT->render($sel), 'tz-hr-tool');

$vositalar .= html_writer::tag('button', get_string('hr_print', 'local_targetzenit'),
    ['type' => 'button', 'class' => 'tz-hr-btn', 'id' => 'tz-hr-print']);

// Tutor/zavuch uchun — butun sinfni bitta bosishda.
if (!$hammasi) {
    $birinchi = reset($oquvchilar);
    $sinf = '';
    if ($birinchi) {
        $g = timetable::groups_of((int) $birinchi, $year);
        $sinf = (string) (reset($g) ?: '');
    }
    if ($sinf !== '' && hisobot::guruh_mumkinmi($year, $sinf)) {
        $vositalar .= html_writer::link(
            new moodle_url('/local/targetzenit/hisobot.php',
                ['guruh' => $sinf, 'hammasi' => 1, 'term' => $termidx]),
            get_string('hr_printgroup', 'local_targetzenit', $sinf),
            ['class' => 'tz-hr-btn tz-hr-btn2']);
    }
}

echo html_writer::div($vositalar
    . html_writer::div(get_string('hr_printhint', 'local_targetzenit'), 'tz-hr-toolhint'),
    'tz-hr-tools');

// ===================================================================== VARAQLAR
echo html_writer::start_div('tz-hr');

$oxirgi = count($oquvchilar) - 1;
$chiqdi = 0;
foreach ($oquvchilar as $i => $sid) {
    $d = hisobot::student((int) $sid, $year, $term);
    if (empty($d->oquvchi)) {
        continue;
    }
    echo hisobot_varaq::chiz($d, $i === $oxirgi);
    $chiqdi++;
}
if (!$chiqdi) {
    echo html_writer::div(get_string('hr_nostudents', 'local_targetzenit'),
        'tz-hr-card tz-hr-empty');
}

echo html_writer::end_div();

// Tugma — inline JS emas, Moodle AMD talab qilmaydigan eng kichik yo'l.
$PAGE->requires->js_amd_inline("
    document.getElementById('tz-hr-print')?.addEventListener('click', function() {
        window.print();
    });
");

echo $OUTPUT->footer();
