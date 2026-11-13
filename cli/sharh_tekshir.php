<?php
/**
 * «Sharhlar yo'qoldi» — TASHXIS vositasi. FAQAT O'QIYDI, hech narsa yozmaydi.
 *
 *   php local/targetzenit/cli/sharh_tekshir.php
 *   php local/targetzenit/cli/sharh_tekshir.php --oqituvchi=HR-EMP-00145
 *
 * MUAMMO (2026-11-13): o'qituvchilar kecha yozgan sharhlari bugun o'zlariga
 * ko'rinmay qoldi. Sharh KO'RINISHI uchun BIR VAQTDA to'rt shart bajarilishi
 * kerak, va har biri alohida buzilishi mumkin:
 *
 *   1. `{comments}` da matn QATORI turishi kerak.
 *      `sharh::select()` da INNER JOIN — comment o'chirilsa qator jimgina
 *      ro'yxatdan chiqib ketadi. Kurs o'chirilsa/qayta yaratilsa Moodle
 *      kontekst bilan birga commentlarni ham o'chiradi.
 *
 *   2. `timetable::current_year()` = `_timetable` dagi ENG KATTA
 *      `academicyear`. Yangi o'quv yili qatori tushsa, u BUGUN o'zgaradi va
 *      `journal::my_courses()` `idnumber LIKE '<yangi yil>::%'` bo'yicha
 *      qidirib HECH NARSA topmaydi — eski yil kurslari ro'yxatdan chiqadi.
 *
 *   3. O'qituvchining kursga YOZILISHI faol bo'lishi kerak
 *      (`my_courses()` da `ue.status = 0`). Tungi sinxron yozilishni
 *      suspend qilsa, kurs o'qituvchining ro'yxatidan tushadi.
 *
 *   4. O'qituvchida shu kursda `local/targetzenit:teach` ruxsati bo'lishi
 *      kerak. Rol yo'qolishi bu tizimda avval ham bo'lgan.
 *
 * 2, 3, 4 — ma'lumot JOYIDA, faqat ko'rinmaydi (tuzatilsa qaytadi).
 * 1 — matn HAQIQATAN yo'q (faqat baza zaxirasidan tiklanadi).
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_targetzenit\timetable;

[$opt, $un] = cli_get_params(['help' => false, 'oqituvchi' => '', 'kun' => 7],
    ['h' => 'help']);

if ($opt['help']) {
    cli_writeln("--oqituvchi=<idnumber>  bitta o'qituvchi bo'yicha to'liq tekshiruv");
    cli_writeln("--kun=<N>               oxirgi N kun kesimi (standart 7)");
    exit(0);
}

$chiziq = str_repeat('=', 68);

// ===================================================== 1. UMUMIY SANOQ
cli_writeln($chiziq);
cli_writeln('1) SHARHLAR BAZADA BORMI');
cli_writeln($chiziq);

$jami = $DB->count_records('local_targetzenit_sharh');
cli_writeln("  `_sharh` qatorlari jami: {$jami}");

// Kun bo'yicha kesim — «kecha yozilgani» bormi degan savolga javob
$kun = max(1, (int) $opt['kun']);
$rows = $DB->get_records_sql("
    SELECT COUNT(1) AS soni, MIN(timecreated) AS boshi
      FROM {local_targetzenit_sharh}
     WHERE timecreated >= :vaqt", ['vaqt' => time() - $kun * 86400]);
$oxirgi = reset($rows);
cli_writeln("  oxirgi {$kun} kunda yozilgan: " . (int) $oxirgi->soni);

$kunlar = $DB->get_records_sql("
    SELECT s.id, s.timecreated FROM {local_targetzenit_sharh} s
     WHERE s.timecreated >= :vaqt ORDER BY s.timecreated DESC",
    ['vaqt' => time() - $kun * 86400], 0, 500);
$sanalar = [];
foreach ($kunlar as $r) {
    $k = userdate($r->timecreated, '%Y-%m-%d');
    $sanalar[$k] = ($sanalar[$k] ?? 0) + 1;
}
foreach ($sanalar as $sana => $soni) {
    cli_writeln("    {$sana}: {$soni}");
}

// ======================================= 2. YETIM QATORLAR (matn yo'q)
cli_writeln('');
cli_writeln($chiziq);
cli_writeln('2) MATN YO\'QOLGANMI  (eng jiddiy holat)');
cli_writeln($chiziq);

$yetim = (int) $DB->count_records_sql("
    SELECT COUNT(1) FROM {local_targetzenit_sharh} s
     WHERE NOT EXISTS (SELECT 1 FROM {comments} c WHERE c.id = s.commentid)");

if ($yetim === 0) {
    cli_writeln("  [yaxshi]  Yetim qator YO'Q — hamma sharhning matni joyida.");
    cli_writeln("            Demak muammo KO'RINISHDA (3/4/5-bo'limlarga qara).");
} else {
    cli_writeln("  [JIDDIY]  {$yetim} qatorning matni `{comments}` da YO'Q.");
    cli_writeln("            Sabab: kurs o'chirilgan yoki QAYTA YARATILGAN —");
    cli_writeln("            Moodle kontekst bilan birga commentlarni o'chiradi.");
    cli_writeln("            Matn FAQAT baza zaxirasidan tiklanadi.");
    cli_writeln('');
    $qaysi = $DB->get_records_sql("
        SELECT s.courseid, COUNT(1) AS soni,
               MAX(CASE WHEN c.id IS NULL THEN 1 ELSE 0 END) AS yoq
          FROM {local_targetzenit_sharh} s
     LEFT JOIN {course} c ON c.id = s.courseid
         WHERE NOT EXISTS (SELECT 1 FROM {comments} cc WHERE cc.id = s.commentid)
      GROUP BY s.courseid ORDER BY COUNT(1) DESC", null, 0, 20);
    foreach ($qaysi as $r) {
        $kurs = $DB->get_field('course', 'idnumber', ['id' => $r->courseid]);
        cli_writeln(sprintf("    kurs #%-7d %-40s %d qator%s",
            $r->courseid, $kurs !== false ? $kurs : '(KURS O\'CHIRILGAN)',
            $r->soni, $kurs === false ? '  <-- kurs yo\'q' : ''));
    }
}

// =============================================== 3. O'QUV YILI ALMASHDIMI
cli_writeln('');
cli_writeln($chiziq);
cli_writeln('3) O\'QUV YILI ALMASHIB KETGANMI  (eng ko\'p uchraydigan sabab)');
cli_writeln($chiziq);

$joriy = timetable::current_year();
cli_writeln("  `current_year()` qaytardi: '{$joriy}'");
cli_writeln("  (bu = `_timetable` dagi ENG KATTA academicyear)");
cli_writeln('');

$yillar = $DB->get_records_sql("
    SELECT academicyear, COUNT(1) AS soni, MAX(timemodified) AS oxirgi
      FROM {local_targetzenit_timetable}
  GROUP BY academicyear ORDER BY academicyear DESC");
cli_writeln("  Jadvaldagi o'quv yillari:");
foreach ($yillar as $y) {
    cli_writeln(sprintf("    %-14s %5d dars   oxirgi o'zgarish: %s%s",
        $y->academicyear, $y->soni,
        $y->oxirgi ? userdate($y->oxirgi, '%Y-%m-%d %H:%M') : '—',
        $y->academicyear === $joriy ? '   <-- JORIY' : ''));
}

// Sharhlar qaysi yilning kurslarida yozilgan
$sharhyil = $DB->get_records_sql("
    SELECT SUBSTRING(c.idnumber FROM 1 FOR POSITION('::' IN c.idnumber) - 1) AS yil,
           COUNT(1) AS soni
      FROM {local_targetzenit_sharh} s
      JOIN {course} c ON c.id = s.courseid
     WHERE c.idnumber LIKE '%::%'
  GROUP BY 1 ORDER BY 2 DESC");
cli_writeln('');
cli_writeln("  Sharhlar qaysi yil kurslarida yozilgan:");
foreach ($sharhyil as $r) {
    $belgi = ($r->yil === $joriy) ? '' : "   <-- JORIY YILGA MOS EMAS, KO'RINMAYDI";
    cli_writeln(sprintf("    %-14s %5d sharh%s", $r->yil, $r->soni, $belgi));
}
if (count($yillar) > 1) {
    cli_writeln('');
    cli_writeln("  ! Jadvalda bir nechta o'quv yili bor. Agar yangi yil qatori");
    cli_writeln("    tushgan bo'lsa, `current_year()` BUGUN o'zgargan va eski");
    cli_writeln("    yil kurslari o'qituvchi ro'yxatidan chiqib ketgan.");
}

// ============================================ 4. BITTA O'QITUVCHI KESIMI
if ($opt['oqituvchi'] !== '') {
    cli_writeln('');
    cli_writeln($chiziq);
    cli_writeln("4) O'QITUVCHI KESIMI: {$opt['oqituvchi']}");
    cli_writeln($chiziq);

    $u = $DB->get_record('user', ['idnumber' => $opt['oqituvchi'], 'deleted' => 0]);
    if (!$u) {
        cli_writeln("  Bunday idnumber bilan foydalanuvchi topilmadi.");
    } else {
        cli_writeln('  ' . fullname($u) . " (user id {$u->id})");

        // Yozgan sharhlari
        $uning = $DB->get_records_sql("
            SELECT s.courseid, COUNT(1) AS soni, MAX(s.timecreated) AS oxirgi
              FROM {local_targetzenit_sharh} s
             WHERE s.authorid = :u GROUP BY s.courseid ORDER BY 3 DESC",
            ['u' => $u->id]);
        cli_writeln('');
        cli_writeln("  Yozgan sharhlari (kurs bo'yicha):");
        if (!$uning) {
            cli_writeln("    YO'Q — bu odam nomidan sharh yozilmagan.");
        }
        foreach ($uning as $r) {
            $c = $DB->get_record('course', ['id' => $r->courseid], 'id, idnumber, fullname');
            cli_writeln(sprintf("    kurs #%-7d %-38s %3d ta, oxirgisi %s",
                $r->courseid, $c ? $c->idnumber : "(KURS O'CHIRILGAN)",
                $r->soni, userdate($r->oxirgi, '%Y-%m-%d %H:%M')));

            if (!$c) {
                cli_writeln("        -> KURS YO'Q: matn ham o'chgan bo'lishi mumkin");
                continue;
            }
            // Yozilish holati
            $ue = $DB->get_records_sql("
                SELECT ue.id, ue.status FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid = :c AND ue.userid = :u",
                ['c' => $c->id, 'u' => $u->id]);
            $faol = false;
            foreach ($ue as $x) {
                if ((int) $x->status === 0) {
                    $faol = true;
                }
            }
            cli_writeln('        yozilish: ' . (!$ue ? "YO'Q (umuman yozilmagan)"
                : ($faol ? 'faol' : 'SUSPEND QILINGAN  <-- shu sabab kurs ro\'yxatdan chiqadi')));

            // teach ruxsati
            $ctx = context_course::instance($c->id, IGNORE_MISSING);
            $teach = $ctx && has_capability('local/targetzenit:teach', $ctx, $u);
            cli_writeln('        `teach` ruxsati: ' . ($teach ? 'bor'
                : "YO'Q  <-- shu sabab kurs ro'yxatdan chiqadi"));

            // Joriy yilga mosmi
            $yil = explode('::', (string) $c->idnumber)[0] ?? '';
            cli_writeln('        o\'quv yili: ' . $yil
                . ($yil === $joriy ? ' (joriy)' : "  <-- JORIY EMAS, KO'RINMAYDI"));
        }

        // `my_courses()` haqiqatda nima qaytaradi
        cli_writeln('');
        $kurslar = \local_targetzenit\journal::my_courses((int) $u->id, $joriy);
        cli_writeln("  `my_courses()` qaytargan kurslar (sahifada SHU ko'rinadi): "
            . count($kurslar));
        foreach ($kurslar as $cid => $nom) {
            cli_writeln("    #{$cid}  " . $nom);
        }
        if (!$kurslar) {
            cli_writeln("    BO'SH — o'qituvchi sahifada «kursingiz yo'q» xabarini ko'radi.");
        }
    }
}

cli_writeln('');
cli_writeln($chiziq);
cli_writeln('XULOSA QANDAY O\'QILADI');
cli_writeln($chiziq);
cli_writeln("  2-bo'limda yetim qator YO'Q + 3-bo'limda yil MOS EMAS");
cli_writeln("     -> sabab O'QUV YILI. `_timetable` dan yangi yil qatorlarini");
cli_writeln("        olib tashlash yoki joriy yilni sozlamaga qotib qo'yish kerak.");
cli_writeln('');
cli_writeln("  4-bo'limda yozilish SUSPEND yoki `teach` YO'Q");
cli_writeln("     -> sabab YOZILISH/ROL. Frappe kurs sinxroni qayta yurgizilsa");
cli_writeln("        yoki rol qo'lda qaytarilsa sharhlar darhol ko'rinadi.");
cli_writeln('');
cli_writeln("  2-bo'limda yetim qator BOR");
cli_writeln("     -> matn haqiqatan o'chgan (kurs qayta yaratilgan). Faqat baza");
cli_writeln("        zaxirasidan tiklanadi. Bu holda darhol xabar bering.");
