<?php
// "Mening oyligim" — xodim o'z tabelini Moodle'da ko'radi.
//
// MAXFIYLIK: `useridnumber` URL dan OLINMAYDI. O'z ma'lumoti tizimga kirgan
// foydalanuvchining `idnumber` idan olinadi, boshqa odamning ma'lumoti esa
// faqat `viewallpayroll` ruxsati bilan va Moodle `user.id` orqali ochiladi.
//
// HISOB-KITOB BU YERDA YO'Q: hamma son Frappe'dan kelgan. Moodle faqat
// ko'rsatadi — shuning uchun ikki tizim orasida farq paydo bo'lmaydi.

require(__DIR__ . '/../../config.php');

use local_targetzenit\payroll;

require_login();

/**
 * Til satri — qisqartma.
 *
 * Sahifada o'nlab satr bor, har birida to'liq `get_string(..., 'local_...')`
 * yozilsa kod o'qilmas bo'lib ketadi.
 */
function tz_s(string $kalit, $a = null): string {
    return get_string($kalit, 'local_targetzenit', $a);
}

$yil = optional_param('yil', 0, PARAM_INT);
$oy = optional_param('oy', 0, PARAM_INT);
$kimuserid = optional_param('userid', 0, PARAM_INT);   // faqat viewallpayroll uchun

$context = context_system::instance();
$url = new moodle_url('/local/targetzenit/oylik.php');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(tz_s('mypayroll'));

$hammasi = has_capability('local/targetzenit:viewallpayroll', $context);

if ($kimuserid && $kimuserid != $USER->id) {
    // Boshqa odamning oyligi — maxfiy ma'lumot.
    require_capability('local/targetzenit:viewallpayroll', $context);
    $kim = core_user::get_user($kimuserid, '*', MUST_EXIST);
} else {
    $kim = $USER;
}

$ref = payroll::ref_for((int) $kim->id);
$PAGE->set_heading($kim->id == $USER->id
    ? tz_s('mypayroll')
    : fullname($kim) . ' — ' . tz_s('mypayroll'));

echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->heading);

if ($ref === '') {
    echo $OUTPUT->notification(
        tz_s('pr_noidnumber'),
        \core\output\notification::NOTIFY_WARNING);
    echo $OUTPUT->footer();
    exit;
}

$oylar = payroll::months($ref);
if (!$oylar) {
    echo $OUTPUT->notification(
        tz_s('pr_nodata'),
        \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

// ----------------------------------------------------------------- oy tanlash
// Bitta select bilan ishlash qulayroq, shuning uchun qiymat "YYYY-M" juftligi.
$tanlov = [];
foreach ($oylar as $o) {
    $tanlov[$o->yil . '-' . $o->oy] = payroll::oy_nomi((int) $o->oy) . ' ' . $o->yil
        . ($o->holat === 'Yopiq' ? '' : ' (' . tz_s('pr_inprogress') . ')');
}

$juft = optional_param('oyjuft', '', PARAM_TEXT);
if (preg_match('/^(\d{4})-(\d{1,2})$/', $juft, $m)) {
    $yil = (int) $m[1];
    $oy = (int) $m[2];
}
$joriy = ($yil && $oy && isset($tanlov["$yil-$oy"])) ? "$yil-$oy" : array_key_first($tanlov);
[$yil, $oy] = array_map('intval', explode('-', $joriy));

$params = [];
if ($kim->id != $USER->id) {
    $params['userid'] = $kim->id;
}
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $url->out(false),
    'class' => 'mb-3 d-flex align-items-center gap-2']);
foreach ($params as $k => $v) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $k, 'value' => $v]);
}
echo html_writer::label(tz_s('pr_month'), 'tz-oy', true, ['class' => 'me-2 mb-0']);
echo html_writer::select($tanlov, 'oyjuft', $joriy, false,
    ['id' => 'tz-oy', 'class' => 'custom-select', 'onchange' => 'this.form.submit()']);
echo html_writer::end_tag('form');

$rec = payroll::month($ref, $yil, $oy);
if (!$rec) {
    echo $OUTPUT->notification(tz_s('pr_nomonth'),
        \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

$soatbay = $rec->tolovturi === 'soat';

if ($rec->holat !== 'Yopiq') {
    echo $OUTPUT->notification(
        tz_s('pr_open_notice', userdate((int) $rec->timemodified)),
        \core\output\notification::NOTIFY_INFO);
}

// ------------------------------------------------------------- asosiy sonlar
$birlik = $soatbay ? tz_s('pr_unit_hour') : tz_s('pr_unit_day');
$kartalar = [
    [tz_s('pr_takehome'), payroll::pul($rec->jami, $rec->valyuta), 'h3 text-primary'],
    [$soatbay ? tz_s('pr_workedhours') : tz_s('pr_workeddays'),
        ($soatbay ? (float) $rec->ishlagan . ' ' . $birlik
            : (float) $rec->ishlagan . ' / ' . $rec->normakun . ' ' . $birlik), 'h4'],
    [tz_s('pr_bonus'), payroll::pul($rec->bonus, $rec->valyuta), 'h4'],
];
echo html_writer::start_div('d-flex flex-wrap gap-3 mb-4');
foreach ($kartalar as [$sarlavha, $qiymat, $klass]) {
    echo html_writer::div(
        html_writer::div(s($sarlavha), 'text-muted small')
        . html_writer::div(s($qiymat), $klass . ' mb-0'),
        'card p-3', ['style' => 'min-width:220px']);
}
echo html_writer::end_div();

// --------------------------------------------------------------- tafsilot
$qatorlar = [
    [$soatbay ? tz_s('pr_hourrate') : tz_s('pr_salary'),
        payroll::pul($rec->oylik, $rec->valyuta)],
];
if (!$soatbay) {
    $qatorlar[] = [tz_s('pr_normdays'), (int) $rec->normakun . ' ' . $birlik];
    $qatorlar[] = [tz_s('pr_dayrate'), payroll::pul($rec->kunliknarx, $rec->valyuta)];
} else {
    $qatorlar[] = [tz_s('pr_workedhours'), (float) $rec->ishlagan . ' ' . $birlik];
}
$qatorlar[] = [tz_s('pr_attended'), (int) $rec->kelgankun . ' ' . tz_s('pr_unit_day')];
$qatorlar[] = [tz_s('pr_bonus_penalty'), payroll::pul($rec->bonus, $rec->valyuta)];
$qatorlar[] = ['<strong>' . tz_s('pr_gross') . '</strong>',
    '<strong>' . payroll::pul($rec->hisob, $rec->valyuta) . '</strong>'];
$qatorlar[] = [tz_s('pr_tax'), '− ' . payroll::pul($rec->nalog, $rec->valyuta)];
$qatorlar[] = [tz_s('pr_meals'), '− ' . payroll::pul($rec->pitaniya, $rec->valyuta)];
$qatorlar[] = ['<strong>' . tz_s('pr_takehome') . '</strong>',
    '<strong>' . payroll::pul($rec->jami, $rec->valyuta) . '</strong>'];

$t = new html_table();
$t->head = [tz_s('pr_indicator'), tz_s('pr_value')];
$t->attributes['class'] = 'generaltable';
$t->data = $qatorlar;
echo $OUTPUT->heading(tz_s('pr_breakdown'), 4);
echo html_writer::table($t);

// ------------------------------------------------------- kunma-kun davomat
$kunlar = payroll::days($rec);
if ($kunlar) {
    echo $OUTPUT->heading(tz_s('pr_days'), 4);
    echo html_writer::div(
        tz_s('pr_days_hint_source') . ' '
        . ($soatbay ? tz_s('pr_days_hint_hours') : tz_s('pr_days_hint_koef')),
        'text-muted small mb-2');

    $kt = new html_table();
    $kt->head = [tz_s('pr_day'), tz_s('pr_weekday'),
        $soatbay ? tz_s('pr_hours') : tz_s('pr_koef'), tz_s('pr_in'), tz_s('pr_out')];
    $kt->attributes['class'] = 'generaltable';
    foreach ($kunlar as $k) {
        $kun = (int) ($k['kun'] ?? 0);
        $vaqt = make_timestamp($yil, $oy, $kun ?: 1);
        $koef = $k['koef'] ?? null;
        if ($koef === null) {
            $belgi = html_writer::span('—', 'text-muted');
        } else if ((float) $koef > 0) {
            $belgi = html_writer::span((float) $koef, 'text-success');
        } else {
            $belgi = html_writer::span('0', 'text-danger');
        }
        $kt->data[] = [
            $kun,
            userdate($vaqt, '%A'),
            $belgi,
            s($k['kirish'] ?? '') ?: html_writer::span('—', 'text-muted'),
            s($k['chiqish'] ?? '') ?: html_writer::span('—', 'text-muted'),
        ];
    }
    echo html_writer::table($kt);
}

echo html_writer::div(tz_s('pr_footnote'), 'text-muted small mt-4');

// ------------------------------------------------------------ admin ro'yxati
if ($hammasi && $kim->id == $USER->id) {
    echo $OUTPUT->heading(tz_s('pr_allstaff', payroll::oy_nomi($oy) . " $yil"), 4);
    $barcha = payroll::all_for_month($yil, $oy);
    $at = new html_table();
    $at->head = [tz_s('pr_employee'), tz_s('pr_position'), tz_s('pr_worked'),
        tz_s('pr_bonus'), tz_s('pr_takehome'), tz_s('pr_linked')];
    $at->attributes['class'] = 'generaltable';
    foreach ($barcha as $b) {
        $ism = $b->moodleuserid
            ? html_writer::link(new moodle_url($url, ['userid' => $b->moodleuserid,
                'oyjuft' => "$yil-$oy"]), s($b->employeename))
            : s($b->employeename);
        $at->data[] = [
            $ism,
            s($b->lavozim),
            (float) $b->ishlagan . ' ' . ($b->tolovturi === 'soat'
                ? tz_s('pr_unit_hour') : tz_s('pr_unit_day')),
            payroll::pul($b->bonus, $b->valyuta),
            payroll::pul($b->jami, $b->valyuta),
            $b->moodleuserid ? '✓' : html_writer::span(tz_s('pr_notlinked'), 'text-danger'),
        ];
    }
    echo html_writer::table($at);
}

echo $OUTPUT->footer();
