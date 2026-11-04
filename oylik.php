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

use local_targetzenit\output\shell;
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
shell::setup($PAGE, tz_s('mypayroll'));

$hammasi = has_capability('local/targetzenit:viewallpayroll', $context);

if ($kimuserid && $kimuserid != $USER->id) {
    // Boshqa odamning oyligi — maxfiy ma'lumot.
    require_capability('local/targetzenit:viewallpayroll', $context);
    $kim = core_user::get_user($kimuserid, '*', MUST_EXIST);
} else {
    $kim = $USER;
}

$ref = payroll::ref_for((int) $kim->id);
$sarlavha = $kim->id == $USER->id
    ? tz_s('mypayroll')
    : fullname($kim) . ' — ' . tz_s('mypayroll');

echo $OUTPUT->header();

if ($ref === '') {
    shell::plain_start($sarlavha);
    echo $OUTPUT->notification(tz_s('pr_noidnumber'),
        \core\output\notification::NOTIFY_WARNING);
    shell::plain_end();
    echo $OUTPUT->footer();
    exit;
}

$oylar = payroll::months($ref);
if (!$oylar) {
    shell::plain_start($sarlavha);
    echo $OUTPUT->notification(tz_s('pr_nodata'),
        \core\output\notification::NOTIFY_INFO);
    shell::plain_end();
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
// Oy tanlagich sarlavha qatorining o'ng tomonida — maketdagi filtr qatori
// bilan bir xil joyda, shunda sahifa bo'ylab bir xil mantiq saqlanadi.
$oyforma = html_writer::start_tag('form', ['method' => 'get',
    'action' => $url->out(false), 'class' => 'tz-dir-f']);
foreach ($params as $k => $v) {
    $oyforma .= html_writer::empty_tag('input',
        ['type' => 'hidden', 'name' => $k, 'value' => $v]);
}
$oyforma .= html_writer::tag('label', tz_s('pr_month'), ['for' => 'tz-oy']);
$oyforma .= html_writer::select($tanlov, 'oyjuft', $joriy, false,
    ['id' => 'tz-oy', 'onchange' => 'this.form.submit()']);
$oyforma .= html_writer::end_tag('form');

$rec = payroll::month($ref, $yil, $oy);
if (!$rec) {
    shell::plain_start($sarlavha, '', $oyforma);
    echo $OUTPUT->notification(tz_s('pr_nomonth'),
        \core\output\notification::NOTIFY_INFO);
    shell::plain_end();
    echo $OUTPUT->footer();
    exit;
}

shell::plain_start($sarlavha,
    payroll::oy_nomi($oy) . ' ' . $yil
        . ($rec->holat === 'Yopiq' ? '' : ' · ' . tz_s('pr_inprogress')),
    $oyforma);

$soatbay = $rec->tolovturi === 'soat';

if ($rec->holat !== 'Yopiq') {
    echo $OUTPUT->notification(
        tz_s('pr_open_notice', userdate((int) $rec->timemodified)),
        \core\output\notification::NOTIFY_INFO);
}

// ------------------------------------------------------------- asosiy sonlar
$birlik = $soatbay ? tz_s('pr_unit_hour') : tz_s('pr_unit_day');

$plita = function(string $yorliq, string $qiymat, string $izoh = '',
                  string $turi = ''): string {
    return html_writer::div(
        html_writer::div(s($yorliq), 'tz-dir-lab')
        . html_writer::div(s($qiymat), 'tz-dir-num' . ($turi ? ' tz-pr-' . $turi : ''))
        . ($izoh !== '' ? html_writer::div(s($izoh), 'tz-dir-sub') : ''),
        'tz-dir-tile');
};

$tiles = $plita(tz_s('pr_takehome'), payroll::pul($rec->jami, $rec->valyuta), '', 'main');
$tiles .= $plita(
    $soatbay ? tz_s('pr_workedhours') : tz_s('pr_workeddays'),
    (float) $rec->ishlagan . ' ' . $birlik,
    $soatbay ? '' : tz_s('pr_ofnorm', (int) $rec->normakun));
$tiles .= $plita(tz_s('pr_bonus'), payroll::pul($rec->bonus, $rec->valyuta));
$tiles .= $plita(tz_s('pr_attended'),
    (int) $rec->kelgankun . ' ' . tz_s('pr_unit_day'));

echo html_writer::div($tiles, 'tz-dir-hero');

// --------------------------------------------------------------- HISOB-KITOB
//
// GORIZONTAL FORMULA: raqamlar ustma-ust ro'yxat emas, CHAPDAN O'NGGA
// o'qiladigan hisob zanjiri. Sabab — oylik aslida formula: oylik ÷ norma
// = kun narxi × ishlangan + bonus = hisoblandi − ushlanmalar = qo'lga.
// Vertikal jadvalda bu bog'lanish ko'rinmaydi, odam "bu son qayerdan
// chiqdi?" deb so'raydi.
//
// Operatorlar Frappe'dagi hisob bilan bir xil (`oylik_tabel.py`):
// kunbay — kunlik narx normaga bo'linadi; soatbay — norma qatnashmaydi.
$pul = fn($x) => payroll::pul($x, $rec->valyuta);

if ($soatbay) {
    $qadam = [
        ['', tz_s('pr_hourrate'), $pul($rec->oylik), ''],
        ['×', tz_s('pr_workedhours'), (float) $rec->ishlagan . ' ' . $birlik, ''],
        ['+', tz_s('pr_bonus_penalty'), $pul($rec->bonus), ''],
        ['=', tz_s('pr_gross'), $pul($rec->hisob), 'res'],
    ];
} else {
    $qadam = [
        ['', tz_s('pr_salary'), $pul($rec->oylik), ''],
        ['÷', tz_s('pr_normdays'), (int) $rec->normakun . ' ' . $birlik, ''],
        ['=', tz_s('pr_dayrate'), $pul($rec->kunliknarx), 'res'],
        ['×', tz_s('pr_workeddays'), (float) $rec->ishlagan . ' ' . $birlik, ''],
        ['+', tz_s('pr_bonus_penalty'), $pul($rec->bonus), ''],
        ['=', tz_s('pr_gross'), $pul($rec->hisob), 'res'],
    ];
}
$qadam[] = ['−', tz_s('pr_tax'), $pul($rec->nalog), 'neg'];
$qadam[] = ['−', tz_s('pr_meals'), $pul($rec->pitaniya), 'neg'];
$qadam[] = ['=', tz_s('pr_takehome'), $pul($rec->jami), 'final'];

echo html_writer::start_div('tz-dir-card');
echo html_writer::tag('h4', tz_s('pr_breakdown'), ['class' => 'tz-dir-cardtitle']);
echo html_writer::div(tz_s('pr_calchint'), 'tz-dir-sub mb-2');

echo html_writer::start_div('tz-pr-calc');
foreach ($qadam as [$op, $yorliq, $qiymat, $turi]) {
    // Operator QADAM ICHIDA turadi: satr ko'chganda u o'z sonidan
    // ajralib qolmaydi va formula o'qilishda buzilmaydi.
    echo html_writer::div(
        ($op !== '' ? html_writer::tag('i', $op, ['class' => 'tz-pr-op']) : '')
        . html_writer::div(
            html_writer::div(s($yorliq), 'tz-pr-lab')
            . html_writer::div(s($qiymat), 'tz-pr-val'), 'tz-pr-box'),
        'tz-pr-step' . ($turi ? ' tz-pr-' . $turi : ''));
}
echo html_writer::end_div();
echo html_writer::end_div();

// ------------------------------------------------------- kunma-kun davomat
$kunlar = payroll::days($rec);
if ($kunlar) {
    echo html_writer::start_div('tz-dir-card');
    echo html_writer::tag('h4', tz_s('pr_days'), ['class' => 'tz-dir-cardtitle']);

    // Kunlar GORIZONTAL: ustun — kun, qator — ko'rsatkich.
    //
    // NEGA: oyda 28-31 kun bor. Har kunni alohida QATOR qilsak sahifa uzun
    // ro'yxatga aylanadi va bir qarashda oyni ko'rib bo'lmaydi. Frappe'dagi
    // oylik tabel ham aynan shu ko'rinishda — kunlar yuqorida.
    //
    // Kirish/chiqish qatorlari FAQAT ma'lumot bo'lsa chiziladi: turniket
    // ID lari hali moslashtirilmagan, bo'sh ikki qator esa bekorga joy oladi.
    $kirish_bor = false;
    $chiqish_bor = false;
    foreach ($kunlar as $k) {
        if (trim((string) ($k['kirish'] ?? '')) !== '') {
            $kirish_bor = true;
        }
        if (trim((string) ($k['chiqish'] ?? '')) !== '') {
            $chiqish_bor = true;
        }
    }

    // Turniket haqidagi jumla faqat o'sha ma'lumot ko'rsatilayotganda
    // chiqadi — aks holda yo'q narsani tushuntirgan bo'lamiz.
    echo html_writer::div(
        ($soatbay ? tz_s('pr_days_hint_hours') : tz_s('pr_days_hint_koef'))
        . (($kirish_bor || $chiqish_bor) ? ' ' . tz_s('pr_days_hint_source') : ''),
        'tz-dir-sub mb-2');

    $sarlavha = [''];
    $koefqator = [html_writer::tag('strong',
        $soatbay ? tz_s('pr_hours') : tz_s('pr_koef'))];
    $kirishqator = [html_writer::tag('strong', tz_s('pr_in'))];
    $chiqishqator = [html_writer::tag('strong', tz_s('pr_out'))];

    foreach ($kunlar as $k) {
        $kun = (int) ($k['kun'] ?? 0);
        $vaqt = make_timestamp($yil, $oy, $kun ?: 1);
        // Dam olish kuni — kulrang, shunda hafta chegaralari ko'rinib turadi.
        $dam = in_array((int) date('w', $vaqt), [0, 6], true);
        $sarlavha[] = html_writer::div(
            html_writer::tag('strong', $kun)
            . html_writer::div(userdate($vaqt, '%a'), 'small'),
            $dam ? 'tz-dir-sub' : '');

        $koef = $k['koef'] ?? null;
        if ($koef === null) {
            $koefqator[] = html_writer::span('—', 'tz-dir-sub');
        } else if ((float) $koef > 0) {
            $koefqator[] = html_writer::span((float) $koef, 'text-success font-weight-bold');
        } else {
            $koefqator[] = html_writer::span('0', 'text-danger');
        }

        $kirishqator[] = s($k['kirish'] ?? '') ?: html_writer::span('·', 'tz-dir-sub');
        $chiqishqator[] = s($k['chiqish'] ?? '') ?: html_writer::span('·', 'tz-dir-sub');
    }

    $kt = new html_table();
    $kt->head = $sarlavha;
    $kt->data = [$koefqator];
    if ($kirish_bor) {
        $kt->data[] = $kirishqator;
    }
    if ($chiqish_bor) {
        $kt->data[] = $chiqishqator;
    }
    $kt->attributes['class'] = 'tz-dir-table tz-kunlar';
    $kt->align = array_merge(['left'], array_fill(0, count($kunlar), 'center'));

    // Tor ekranda jadval SAHIFANI emas, o'zini surib ko'rsatadi
    // (uslub `styles.css` da — inline `<style>` sahifada takrorlanardi).
    echo html_writer::div(html_writer::table($kt), 'tz-dir-tablewrap');
    echo html_writer::end_div();
}

// ------------------------------------------- oylar kesimida hisob-kitob
// Sonlar Frappe'dan keladi va investor panelidagi bilan AYNAN BIR XIL —
// manba bitta (`investor_dashboard.personal_months`). Shuning uchun bu yerda
// hech narsa hisoblanmaydi, faqat chiziladi.
$hisob = payroll::settle($ref);
if ($hisob) {
    echo html_writer::start_div('tz-dir-card');
    echo html_writer::tag('h4', tz_s('st_title'), ['class' => 'tz-dir-cardtitle']);
    echo html_writer::div(tz_s('st_hint'), 'tz-dir-sub mb-2');

    // Joriy oy bu jadvalda bo'lmasligi KUTILGAN holat: nachisleniya
    // buxgalteriyada oy yopilganda yoziladi. Tushuntirilmasa o'qituvchi
    // "oktabrdamiz-ku, nega sentabr?" deb o'ylaydi.
    $eng_yangi = '';
    foreach ($hisob as $m) {
        if ($m->oy > $eng_yangi) {
            $eng_yangi = $m->oy;
        }
    }
    $joriy_oy = sprintf('%04d-%02d', $yil, $oy);
    if ($eng_yangi < $joriy_oy) {
        echo $OUTPUT->notification(
            tz_s('st_currentmissing', payroll::oy_yorlig($joriy_oy)),
            \core\output\notification::NOTIFY_INFO);
    }

    // Qotib yozilgan rang qorong'i rejimda o'qilmaydi — tokenlardan olamiz
    $yashil = 'color:var(--tz-good);white-space:nowrap';
    $qizil = 'color:var(--tz-critical);white-space:nowrap';
    // Holat chipi — bizning token qatlamimiz (Bootstrap `badge` emas):
    // rang sayt temasiga moslashadi va matn har doim yonida turadi.
    $belgilar = [
        'paid' => ['st_s_paid', 'tz-dir-chip tz-dir-good'],
        'over' => ['st_s_over', 'tz-dir-chip tz-dir-good'],
        'partial' => ['st_s_partial', 'tz-dir-chip tz-dir-warning'],
        'unpaid' => ['st_s_unpaid', 'tz-dir-chip tz-dir-critical'],
        'advance' => ['st_s_advance', 'tz-dir-chip tz-dir-good'],
    ];

    $ht = new html_table();
    $ht->attributes['class'] = 'tz-dir-table';
    $ht->head = [tz_s('st_month'), tz_s('st_salary'), tz_s('st_accrued'),
        tz_s('st_paid'), tz_s('st_remaining'), tz_s('st_status')];
    $ht->align = ['left', 'right', 'right', 'right', 'right', 'left'];

    $jami = [];
    foreach ($hisob as $m) {
        $cur = $m->valyuta;
        if (!isset($jami[$cur])) {
            $jami[$cur] = ['nach' => 0, 'paid' => 0, 'debt' => 0, 'oysum' => 0, 'oyn' => 0];
        }
        $jami[$cur]['nach'] += $m->nach;
        $jami[$cur]['paid'] += $m->paid;
        $jami[$cur]['debt'] += $m->debt;
        if ($m->oylik > 0.5) {
            $jami[$cur]['oysum'] += $m->oylik;
            $jami[$cur]['oyn']++;
        }

        // Qoldiq: musbat = to'lanmagan (qizil), manfiy = ortiq to'langan
        // (yashil, oldida +), nolga yaqin = oddiy 0.
        if ($m->debt > 0.5) {
            $qoldiq = html_writer::span(payroll::pul($m->debt, $cur), '', ['style' => $qizil]);
        } else if ($m->debt < -0.5) {
            $qoldiq = html_writer::span('+' . payroll::pul(-$m->debt, $cur), '',
                ['style' => $yashil]);
        } else {
            $qoldiq = html_writer::span('0', 'tz-dir-sub');
        }

        $tolangan = html_writer::span(payroll::pul($m->paid, $cur), '', ['style' => $yashil]);
        if ($m->advance > 0.5) {
            $tolangan .= html_writer::div(
                tz_s('st_unlinked', payroll::pul($m->advance, $cur)), 'tz-dir-sub');
        }

        [$kalit, $klass] = $belgilar[payroll::holat($m)];

        $ht->data[] = [
            payroll::oy_yorlig($m->oy) . ' ' . html_writer::span(s($cur), 'tz-dir-sub'),
            $m->oylik > 0.5
                ? payroll::pul($m->oylik, $cur)
                : html_writer::span('—', 'tz-dir-sub', ['title' => tz_s('st_nosalary')]),
            payroll::pul($m->nach, $cur),
            $tolangan,
            $qoldiq,
            html_writer::span(tz_s($kalit), $klass),
        ];
    }

    // JAMI — har valyuta uchun alohida. Ish haqi ustunida O'RTACHA ko'rsatiladi:
    // oylik summalarni qo'shib bo'lmaydi, ular bir oyning qiymati.
    foreach ($jami as $cur => $t) {
        $ht->data[] = [
            html_writer::tag('strong', tz_s('st_total'))
                . ' ' . html_writer::span(s($cur), 'tz-dir-sub'),
            $t['oyn']
                ? html_writer::tag('strong', payroll::pul($t['oysum'] / $t['oyn'], $cur))
                    . html_writer::div(tz_s('st_average'), 'tz-dir-sub')
                : html_writer::span('—', 'tz-dir-sub'),
            html_writer::tag('strong', payroll::pul($t['nach'], $cur)),
            html_writer::tag('strong', payroll::pul($t['paid'], $cur), ['style' => $yashil]),
            html_writer::tag('strong',
                $t['debt'] < -0.5 ? '+' . payroll::pul(-$t['debt'], $cur)
                                  : payroll::pul($t['debt'], $cur),
                ['style' => $t['debt'] > 0.5 ? $qizil : $yashil]),
            '',
        ];
    }

    echo html_writer::div(html_writer::table($ht), 'tz-dir-tablewrap');
    echo html_writer::end_div();
}

echo html_writer::div(tz_s('pr_footnote'), 'tz-dir-sub mt-3');

// ------------------------------------------------------------ admin ro'yxati
if ($hammasi && $kim->id == $USER->id) {
    echo html_writer::start_div('tz-dir-card');
    echo html_writer::tag('h4', tz_s('pr_allstaff', payroll::oy_nomi($oy) . " $yil"),
        ['class' => 'tz-dir-cardtitle']);
    $barcha = payroll::all_for_month($yil, $oy);
    $at = new html_table();
    $at->head = [tz_s('pr_employee'), tz_s('pr_position'), tz_s('pr_worked'),
        tz_s('pr_bonus'), tz_s('pr_takehome'), tz_s('pr_linked')];
    $at->attributes['class'] = 'tz-dir-table';
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
    echo html_writer::div(html_writer::table($at), 'tz-dir-tablewrap');
    echo html_writer::end_div();
}

shell::plain_end();
echo $OUTPUT->footer();
