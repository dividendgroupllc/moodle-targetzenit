<?php
/**
 * «O'qituvchi o'z guruhini ko'rmayapti» — TASHXIS. FAQAT O'QIYDI.
 *
 *   php local/targetzenit/cli/baholash_tekshir.php --oqituvchi=HR-EMP-00123
 *   php local/targetzenit/cli/baholash_tekshir.php --oqituvchi=nida.ismi --guruh="4 A(full)"
 *
 * MUAMMO (2026-11-19): fan o'qituvchisi `baholash.php` da o'ziga biriktirilgan
 * guruhning ball setkasini ko'rmayapti. Guruh KO'RINISHI uchun bir vaqtda
 * BESH shart bajarilishi kerak, va har biri alohida buzilishi mumkin:
 *
 *   1. Moodle'da kurs bor (`<yil>::<guruh>::<fan>` idnumber bilan).
 *   2. Shu kurs uchun BAHOLASH va unda SITTING yaratilgan
 *      (`ensure_sittings()` fan + sinf darajasi bo'yicha topadi).
 *   3. O'qituvchi o'sha kursga YOZILGAN (faol) — YOKI jadvalda
 *      `teacherkey = user.idnumber` bilan turibdi (suspend tuzog'i yo'li).
 *   4. O'qituvchida `gradeexam` ruxsati bor (ROL biriktirilishi bo'yicha).
 *   5. Sitting holati `bekor` EMAS.
 *
 * Skript har bir shartni alohida tekshirib, qaysi bosqichda uzilayotganini
 * aytadi. Taxmin qilish shart emas.
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_targetzenit\exam;
use local_targetzenit\sharh;
use local_targetzenit\timetable;

list($options) = cli_get_params(
    ['help' => false, 'oqituvchi' => '', 'guruh' => '', 'fan' => ''],
    ['h' => 'help']
);

if ($options['help'] || $options['oqituvchi'] === '') {
    cli_writeln("Ishlatish:");
    cli_writeln("  php baholash_tekshir.php --oqituvchi=<idnumber|username|email>");
    cli_writeln("                           [--guruh=\"4 A(full)\"] [--fan=ENGLISH]");
    exit(0);
}

$kalit = trim($options['oqituvchi']);
$guruhfiltr = trim($options['guruh']);
$fanfiltr = trim($options['fan']);

// ----------------------------------------------------------- 0. Foydalanuvchi
$user = $DB->get_record('user', ['idnumber' => $kalit])
    ?: $DB->get_record('user', ['username' => $kalit])
    ?: $DB->get_record('user', ['email' => $kalit]);

if (!$user) {
    cli_error("Foydalanuvchi topilmadi: $kalit");
}
$uid = (int) $user->id;

cli_writeln(str_repeat('=', 72));
cli_writeln("O'QITUVCHI: {$user->lastname} {$user->firstname}");
cli_writeln("  moodle id : $uid");
cli_writeln("  idnumber  : '" . $user->idnumber . "'"
    . ($user->idnumber === '' ? '   <-- BO`SH! jadval yo`li ishlamaydi' : ''));
cli_writeln("  username  : {$user->username}");
cli_writeln("  suspended : " . ((int) $user->suspended ? 'HA' : "yo'q"));

$yil = timetable::current_year();
cli_writeln("  o'quv yili: '$yil'");
cli_writeln(str_repeat('=', 72));

// ----------------------------------------------- 1. Moodle yozilishi (faol)
$faol = enrol_get_users_courses($uid, true, 'id, fullname, idnumber');
cli_writeln("\n[1] MOODLE'DA FAOL YOZILISH: " . count($faol) . " kurs");
foreach ($faol as $c) {
    cli_writeln("      {$c->id}  " . ($c->idnumber ?: '(idnumber yo`q)'));
}

// Suspend qilingan yozilishlar ham ko'rsatiladi — asosiy tuzoq shu.
$hammasi = enrol_get_users_courses($uid, false, 'id, fullname, idnumber');
$suspend = array_diff_key($hammasi, $faol);
cli_writeln("\n[1b] SUSPEND QILINGAN YOZILISH: " . count($suspend) . " kurs"
    . (count($suspend) ? '   <-- kurs sinxroni olib tashlagan' : ''));
foreach ($suspend as $c) {
    cli_writeln("      {$c->id}  " . ($c->idnumber ?: '(idnumber yo`q)'));
}

// ------------------------------------------------------- 2. Jadval yo'li
$jadval = $yil !== '' ? sharh::jadval_kurslarim($uid, $yil) : [];
cli_writeln("\n[2] JADVALDA (teacherkey = idnumber): " . count($jadval) . " kurs");
foreach ($jadval as $c) {
    cli_writeln("      {$c->id}  {$c->idnumber}");
}
if (!$jadval && $user->idnumber !== '') {
    $n = $DB->count_records('local_targetzenit_timetable',
        ['academicyear' => $yil, 'teacherkey' => $user->idnumber]);
    cli_writeln("      (jadvalda '{$user->idnumber}' kaliti bilan $n qator)");
    $namuna = $DB->get_records_sql(
        "SELECT DISTINCT teacherkey FROM {local_targetzenit_timetable}
          WHERE academicyear = :y AND teacherkey <> '' ", ['y' => $yil], 0, 5);
    cli_writeln("      jadvaldagi kalit namunalari: "
        . implode(', ', array_keys($namuna)));
}

// --------------------------------------------- 3. exam::mening_kurslarim()
$mening = exam::mening_kurslarim($uid);
cli_writeln("\n[3] exam::mening_kurslarim() -> " . count($mening) . " kurs");
cli_writeln("    (baholash.php ro'yxatni SHU bo'yicha kesadi)");
foreach ($mening as $cid) {
    $c = $DB->get_record('course', ['id' => $cid], 'id, idnumber');
    cli_writeln("      $cid  " . ($c->idnumber ?? '?'));
}
if (!$mening) {
    cli_writeln("    !! BO'SH — o'qituvchi hech qanday guruhni ko'rmaydi.");
}

// ------------------------------------------- 4. Tanlangan guruh bo'yicha
if ($guruhfiltr !== '') {
    cli_writeln("\n" . str_repeat('-', 72));
    cli_writeln("GURUH BO'YICHA: '$guruhfiltr'"
        . ($fanfiltr !== '' ? " / fan '$fanfiltr'" : ''));

    $naqsh = $DB->sql_like_escape($yil) . '::' . $DB->sql_like_escape($guruhfiltr) . '::%';
    $kurslar = $DB->get_records_select('course',
        $DB->sql_like('idnumber', ':p'), ['p' => $naqsh], '', 'id, idnumber, fullname');
    cli_writeln("  Moodle kurslari: " . count($kurslar));

    foreach ($kurslar as $c) {
        $fan = explode('::', $c->idnumber)[2] ?? '';
        if ($fanfiltr !== '' && strcasecmp($fan, $fanfiltr) !== 0) {
            continue;
        }
        cli_writeln("\n  --- kurs {$c->id}  {$c->idnumber}");

        $ctx = context_course::instance((int) $c->id);
        cli_writeln("      gradeexam ruxsati : "
            . (has_capability('local/targetzenit:gradeexam', $ctx, $uid) ? 'BOR' : "YO'Q"));
        cli_writeln("      mening_kurslarim da: "
            . (in_array((int) $c->id, $mening, true) ? 'BOR' : "YO'Q"));

        $sits = $DB->get_records_sql("
            SELECT s.id, s.status, s.groupname, e.name, e.turi, e.termname,
                   e.subjectkey, e.gradelevel, e.status AS examstatus,
                   (SELECT COUNT(1) FROM {local_targetzenit_exam_result} r
                     WHERE r.sittingid = s.id) AS ballar
              FROM {local_targetzenit_exam_sitting} s
              JOIN {local_targetzenit_exam} e ON e.id = s.examid
             WHERE s.courseid = :c", ['c' => $c->id]);
        cli_writeln("      baholash sitting  : " . count($sits));
        foreach ($sits as $s) {
            cli_writeln("         [{$s->id}] {$s->name} | {$s->turi} {$s->termname}"
                . " | fan={$s->subjectkey} daraja={$s->gradelevel}"
                . " | sitting={$s->status} exam={$s->examstatus}"
                . " | ball={$s->ballar}"
                . ($s->status === 'bekor' ? '   <-- CHIQARILGAN' : ''));
        }
        if (!$sits) {
            cli_writeln("         !! Bu kursga BAHOLASH yaratilmagan —");
            cli_writeln("            `ensure_sittings()` fan+daraja bo'yicha topadi,");
            cli_writeln("            ya'ni baholashda fan '{$fan}' va shu sinf darajasi");
            cli_writeln("            tanlanganmi, tekshiring.");
        }
    }
}

cli_writeln("\n" . str_repeat('=', 72));
cli_writeln("XULOSA:");
if (!$mening) {
    cli_writeln("  Ro'yxat bo'sh. Sabab [1]/[2]/[4] bo'limlaridan ko'rinadi:");
    cli_writeln("   - faol yozilish ham, jadval yo'li ham bo'sh bo'lsa:");
    cli_writeln("     o'qituvchi kursga umuman biriktirilmagan;");
    cli_writeln("   - jadvalda kalit boshqa bo'lsa: user.idnumber va");
    cli_writeln("     timetable.teacherkey mos kelmayapti (unic-id qoidasi).");
} else {
    cli_writeln("  Ro'yxatda " . count($mening) . " kurs bor.");
    cli_writeln("  Agar kerakli guruh shunda bo'lsa-yu ekranda ko'rinmasa —");
    cli_writeln("  o'sha kursga BAHOLASH (sitting) yaratilmagan: [4] ga qarang.");
}
cli_writeln(str_repeat('=', 72));
