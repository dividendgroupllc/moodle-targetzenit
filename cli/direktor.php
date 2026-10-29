<?php
/**
 * Direktor paneli — reja/fakt qatlamini boshqarish va diagnostika.
 *
 *   php local/targetzenit/cli/direktor.php --holat
 *   php local/targetzenit/cli/direktor.php --staff
 *   php local/targetzenit/cli/direktor.php --reja
 *   php local/targetzenit/cli/direktor.php --fakt [--from=2026-09-01 --to=2026-10-29]
 *   php local/targetzenit/cli/direktor.php --aicat
 *   php local/targetzenit/cli/direktor.php --hammasi
 *
 * `--hammasi` — birinchi o'rnatishdan keyin bir marta yurgiziladi:
 * o'qituvchilar -> reja -> AI indeksi -> fakt (tartib muhim).
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_targetzenit\aicat;
use local_targetzenit\fact;
use local_targetzenit\norm;
use local_targetzenit\slot;
use local_targetzenit\staff;
use local_targetzenit\timetable;

[$opt, $un] = cli_get_params(
    ['help' => false, 'holat' => false, 'staff' => false, 'reja' => false,
     'fakt' => false, 'aicat' => false, 'hammasi' => false,
     'from' => '', 'to' => '', 'year' => ''],
    ['h' => 'help']
);

$ish = $opt['holat'] || $opt['staff'] || $opt['reja'] || $opt['fakt']
    || $opt['aicat'] || $opt['hammasi'];

if ($opt['help'] || !$ish) {
    cli_writeln("--holat    reja/fakt qatlamining holatini ko'rsatadi");
    cli_writeln("--staff    o'qituvchilar ro'yxatini (kalit ko'prigi) qayta quradi");
    cli_writeln("--reja     dars o'rinlarini (slot) qayta quradi");
    cli_writeln("--fakt     faktni qayta hisoblaydi [--from=... --to=...]");
    cli_writeln("--aicat    AI kategoriya indeksini JSON'lardan qayta quradi");
    cli_writeln("--hammasi  hammasini to'g'ri tartibda (birinchi o'rnatish uchun)");
    cli_writeln("--year     o'quv yili (standart: jadvaldagi oxirgisi)");
    exit(0);
}

$year = $opt['year'] ?: timetable::current_year();
if ($year === '') {
    cli_error("Jadval bo'sh — o'quv yili aniqlanmadi. Frappe'dan jadvalni yuboring.");
}
cli_writeln("O'quv yili: {$year}");

$log = function($x) { cli_writeln($x); };

// ------------------------------------------------------------------ HOLAT
if ($opt['holat']) {
    $terms = timetable::terms($year);
    cli_writeln('');
    cli_writeln('--- Manba');
    cli_writeln(str_pad('jadval qatorlari', 26)
        . $DB->count_records('local_targetzenit_timetable', ['academicyear' => $year]));
    cli_writeln(str_pad('choraklar', 26) . count($terms));
    $bayram = count(timetable::holidays($year));
    cli_writeln(str_pad('bayramlar', 26) . $bayram);
    if (!$bayram) {
        cli_writeln('  ! Bayram ro\'yxati bo\'sh — bayram kunlari ham DARS KUNI deb');
        cli_writeln('    hisoblanadi va o\'qituvchida "dars o\'tmagan" bo\'lib ko\'rinadi.');
        cli_writeln('    Frappe\'dan kalendarni bayramlar bilan yuborish kerak.');
    }

    cli_writeln('');
    cli_writeln("--- O'qituvchilar");
    foreach ([staff::OK => 'bog\'langan', staff::USERLESS => 'Moodle useri yo\'q',
              staff::LEGACY => 'Employee bog\'lanmagan'] as $h => $nom) {
        cli_writeln(str_pad($nom, 26)
            . $DB->count_records('local_targetzenit_staff',
                ['academicyear' => $year, 'holat' => $h]));
    }
    $muammo_dars = 0;
    foreach (staff::problems($year) as $p) {
        $muammo_dars += (int) $p->lessonsweek;
        cli_writeln("  ! {$p->holat}: {$p->teacherkey} — {$p->teachername} "
            . "({$p->lessonsweek} dars/hafta)");
    }
    if ($muammo_dars) {
        cli_writeln("  => haftada {$muammo_dars} dars hech kimning hisobotiga tushmaydi.");
        cli_writeln("     `INSTR-...vakant` / `INSTR-TUTOR` — bu VAKANSIYA, xato emas:");
        cli_writeln("     o'qituvchi tayinlanmagan. Qolganlari esa Frappe'da `Instructor`");
        cli_writeln("     ga `Employee` bog'lab, jadvalni qayta yuborish bilan tuzatiladi.");
    }

    cli_writeln('');
    cli_writeln('--- Reja va fakt');
    cli_writeln(str_pad('slotlar', 26)
        . $DB->count_records('local_targetzenit_slot', ['academicyear' => $year]));
    cli_writeln(str_pad('  shundan muzlatilgan', 26)
        . $DB->count_records('local_targetzenit_slot',
            ['academicyear' => $year, 'frozen' => 1]));
    cli_writeln(str_pad('fakt qatorlari', 26)
        . $DB->count_records('local_targetzenit_fact'));
    cli_writeln(str_pad('  mavzu yozilgan', 26)
        . $DB->count_records('local_targetzenit_fact', ['hastopic' => 1]));
    cli_writeln(str_pad('  yozuv bor', 26)
        . $DB->count_records_select('local_targetzenit_fact',
            "audiostatus IN ('ready', 'transcribed', 'deleted')"));
    cli_writeln(str_pad('  tahlil qilingan', 26)
        . $DB->count_records('local_targetzenit_fact', ['analysestatus' => 'ok']));
    cli_writeln(str_pad('  tahlil yiqilgan', 26)
        . $DB->count_records('local_targetzenit_fact', ['analysestatus' => 'failed']));
    cli_writeln(str_pad('  o\'rnini bosilgan', 26)
        . $DB->count_records('local_targetzenit_fact', ['substituted' => 1]));
    cli_writeln(str_pad('AI kategoriya ballari', 26)
        . $DB->count_records('local_targetzenit_aicat'));

    // --- BIRLASHTIRISH SOG'LIGI
    //
    // "mavzu yozilgan" raqami kichik chiqsa, ikki sabab bo'lishi mumkin:
    // (a) o'qituvchilar jurnalni hali to'ldirmagan, (b) slot bilan dars
    // yozuvi BIRLASHMAGAN (kurs idnumber mos kelmagan). Ikkisini ajratish
    // kerak — aks holda butun panel jim noto'g'ri bo'lib qoladi.
    cli_writeln('');
    cli_writeln('--- Birlashtirish sog\'ligi');
    $kurssiz = $DB->count_records_select('local_targetzenit_slot',
        'academicyear = :y AND courseid IS NULL', ['y' => $year]);
    cli_writeln(str_pad('slot: kurs topilmagan', 26) . $kurssiz);
    if ($kurssiz) {
        cli_writeln('  ! bu slotlarga dars yozuvi HECH QACHON bog\'lanmaydi');
        cli_writeln('    (jadvaldagi courseidnumber Moodle kursida yo\'q)');
    }

    $darslar_jami = $DB->count_records('local_targetzenit_lesson');
    $bogli = $DB->count_records_select('local_targetzenit_fact', 'lessonid IS NOT NULL');
    cli_writeln(str_pad('dars yozuvi (_lesson)', 26) . $darslar_jami);
    cli_writeln(str_pad('  shundan slotga bog\'landi', 26) . $bogli);
    cli_writeln(str_pad('  bog\'lanmadi', 26) . max(0, $darslar_jami - $bogli));
    if ($darslar_jami > 0 && $bogli < $darslar_jami) {
        cli_writeln('  ! bog\'lanmagan yozuvlar jadvaldan tashqarida o\'tilgan');
        cli_writeln('    darslar bo\'lishi mumkin (dars raqami jadvalga mos kelmagan).');
        cli_writeln('    Ko\'pi bog\'lanmasa — kurs yoki dars raqami mosligini tekshirish kerak.');
    }

    cli_writeln('');
    cli_writeln('--- Normalar');
    if (!$DB->get_manager()->table_exists('local_targetzenit_norm')) {
        cli_writeln('  ! `_norm` jadvali yo\'q — plagin yangilanishi qo\'llanmagan');
    } else {
        cli_writeln(str_pad('Frappe\'dan kelgan', 26)
            . $DB->count_records('local_targetzenit_norm', ['academicyear' => $year]));
        foreach (array_keys(norm::ZAXIRA) as $kod) {
            $n = norm::get($kod, ['year' => $year]);
            cli_writeln('  ' . str_pad($kod, 24) . $n['value'] . '  (' . $n['source'] . ')');
        }
    }
}

// ------------------------------------------------------------- O'QITUVCHILAR
if ($opt['staff'] || $opt['hammasi']) {
    cli_writeln('');
    cli_writeln("O'qituvchilar ro'yxati quriladi...");
    $r = staff::rebuild($year);
    cli_writeln("  {$r['jami']} ta: {$r['ok']} bog'langan, "
        . "{$r['userless']} user yo'q, {$r['legacykey']} eski kalit, "
        . "{$r['ochirildi']} o'chirildi");
    foreach (staff::problems($year) as $p) {
        cli_writeln("  ! {$p->holat}: {$p->teacherkey} — {$p->teachername}");
    }
}

// -------------------------------------------------------------------- REJA
if ($opt['reja'] || $opt['hammasi']) {
    cli_writeln('');
    cli_writeln('Reja (slot) quriladi...');
    $r = slot::build($year, $opt['from'] ?: null, $opt['to'] ?: null, $log);
    cli_writeln("  {$r['kunlar']} dars kuni; +{$r['qoshildi']} qo'shildi, "
        . "{$r['yangilandi']} yangilandi, {$r['ochirildi']} o'chirildi, "
        . "{$r['tegilmadi']} o'tmish tegilmadi");
}

// ------------------------------------------------------------- AI INDEKSI
if ($opt['aicat'] || $opt['hammasi']) {
    cli_writeln('');
    cli_writeln('AI kategoriya indeksi quriladi...');
    $r = aicat::backfill($log);
    cli_writeln("  {$r['darslar']} tahlil: {$r['ok']} o'qildi, {$r['bosh']} bo'sh, "
        . "{$r['ballar']} ball yozildi");
}

// -------------------------------------------------------------------- FAKT
if ($opt['fakt'] || $opt['hammasi']) {
    $terms = timetable::terms($year);
    if (!$terms) {
        cli_error("Kalendar yo'q — fakt hisoblanmadi.");
    }
    $from = $opt['from'] ?: min(array_map(fn($t) => $t->startdate, $terms));
    $to = $opt['to'] ?: max(array_map(fn($t) => $t->enddate, $terms));

    cli_writeln('');
    cli_writeln("Fakt hisoblanadi {$from} … {$to} ...");
    $r = fact::refresh($from, $to, $log);
    cli_writeln("  {$r['slotlar']} slot: +{$r['qoshildi']} qo'shildi, "
        . "{$r['yangilandi']} yangilandi, {$r['tegilmadi']} o'zgarmadi");
}

cli_writeln('');
cli_writeln('Tayyor.');
