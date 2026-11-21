<?php
/**
 * «Psixolog» rolini yaratish — BIR MARTALIK vosita (PSIXOLOG_ARXITEKTURA.md).
 *
 *   php local/targetzenit/cli/psixolog_rol.php --yarat
 *   php local/targetzenit/cli/psixolog_rol.php --qidir=Aliyeva
 *   php local/targetzenit/cli/psixolog_rol.php --ber=HR-EMP-00001
 *   php local/targetzenit/cli/psixolog_rol.php --bergan
 *   php local/targetzenit/cli/psixolog_rol.php --tekshir=HR-EMP-00001
 *
 * NEGA PLAGIN O'ZI YARATMAYDI: rol yaratish ma'murning qarori; migratsiyada
 * avtomatik yaratish jim yiqilishi va qo'lda sozlamani har deployda qaytarib
 * yuborishi mumkin (`koordinator_rol.php` bilan bir xil sabab).
 *
 * BITTA ROL YETADI (koordinatordan farqli): bizning sahifalar tizim
 * kontekstidagi `psixolog` ruxsati bilan yuradi; yadro ishlari (quiz/feedback
 * yaratish) esa MARKAZIY KURSdagi oddiy `editingteacher` yozilishidan keladi —
 * uni Frappe sinxoni (moodle_psixologiya.py) qiladi. `--ber` kurs mavjud
 * bo'lsa zudlik bilan o'zi ham yozib qo'yadi (sinxonni kutmaslik uchun).
 *
 * ARXETIP ATAYLAB BO'SH, `manager` ham YO'Q: suhbat/case matnlari FAQAT
 * psixologga (maxfiylik qarori, 2026-11-21) — manager arxetipi direktorga
 * ham ochib yuborardi.
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->libdir . '/enrollib.php');

use local_targetzenit\psixolog;

/** Tizim kontekstidagi rol. */
const TZP_ROL = 'tzpsixolog';

/** Ruxsatlar (tizim kontekstida). */
const TZP_RUXSATLAR = [
    'local/targetzenit:psixolog',
    // Butun maktab jadvalini ko'rish — suhbatga qulay vaqt tanlash uchun.
    'local/targetzenit:viewalltimetable',
    // Odam qidirish/profil ko'rish (koordinator bilan bir xil juftlik).
    'moodle/site:viewparticipants',
    'moodle/user:viewdetails',
];

[$opt, $un] = cli_get_params(
    ['help' => false, 'yarat' => false, 'bergan' => false, 'ber' => '',
     'qidir' => '', 'tekshir' => ''],
    ['h' => 'help']
);

if ($opt['help'] || (!$opt['yarat'] && !$opt['bergan'] && $opt['ber'] === ''
        && $opt['qidir'] === '' && $opt['tekshir'] === '')) {
    cli_writeln("--yarat              rolni yaratadi (bor bo'lsa ruxsatlarini tiklaydi)");
    cli_writeln("--qidir=<matn>       xodimni topadi va Employee ID sini ko'rsatadi");
    cli_writeln("--ber=<idnumber>     rolni beradi + markaziy kursga editingteacher yozadi");
    cli_writeln("--bergan             rol kimga berilganini ko'rsatadi");
    cli_writeln("--tekshir=<idnumber> ruxsatlar BOR-YO'Qligini ko'rsatadi");
    exit(0);
}

$syscontext = context_system::instance();

// -------------------------------------------------------------------- YARAT
if ($opt['yarat']) {
    $rol = $DB->get_record('role', ['shortname' => TZP_ROL]);
    if (!$rol) {
        $rolid = create_role('Psixolog', TZP_ROL,
            "Maktab psixologi: individual suhbat qaydlari, muammolar (case), "
            . "test/so'rovnoma. Suhbat matnlarini faqat shu rol ko'radi.", '');
        $rol = $DB->get_record('role', ['id' => $rolid]);
        cli_writeln('Rol yaratildi: ' . TZP_ROL . " id={$rolid}");
    } else {
        cli_writeln('Rol bor: ' . TZP_ROL . " id={$rol->id} — ruxsatlari tiklanadi");
    }
    set_role_contextlevels($rol->id, [CONTEXT_SYSTEM]);

    foreach (TZP_RUXSATLAR as $cap) {
        if (!get_capability_info($cap, false)) {
            cli_writeln("  ! ruxsat topilmadi: {$cap} — plagin yangilanganmi?");
            continue;
        }
        assign_capability($cap, CAP_ALLOW, $rol->id, $syscontext->id, true);
        cli_writeln("  + {$cap}");
    }
    $syscontext->mark_dirty();
    cli_writeln('');
    cli_writeln('Tayyor. Rolni berish: --ber=<Employee ID>');
}

// -------------------------------------------------------------------- QIDIR
// Faqat KO'RSATISH — rol berish baribir `idnumber` bilan (unic-id qoidasi).
if ($opt['qidir'] !== '') {
    $q = '%' . $DB->sql_like_escape($opt['qidir']) . '%';
    $rows = $DB->get_records_select('user',
        "deleted = 0 AND idnumber <> '' AND ("
        . $DB->sql_like('firstname', ':q1', false) . ' OR '
        . $DB->sql_like('lastname', ':q2', false) . ' OR '
        . $DB->sql_like('idnumber', ':q3', false) . ')',
        ['q1' => $q, 'q2' => $q, 'q3' => $q],
        'lastname ASC', 'id, firstname, lastname, idnumber, email', 0, 30);

    if (!$rows) {
        cli_writeln("Topilmadi. Xodim Frappe'da `Employee` bo'lib, sinxron o'tganmi?");
    }
    foreach ($rows as $u) {
        cli_writeln('  ' . str_pad(fullname($u), 32)
            . str_pad((string) $u->idnumber, 18) . $u->email);
    }
}

// ---------------------------------------------------------------------- BER
if ($opt['ber'] !== '') {
    $rol = $DB->get_record('role', ['shortname' => TZP_ROL]);
    if (!$rol) {
        cli_error("Rol yo'q — avval --yarat");
    }
    $user = $DB->get_record('user', ['idnumber' => $opt['ber'], 'deleted' => 0]);
    if (!$user) {
        cli_error("Bunday idnumber bilan foydalanuvchi topilmadi: {$opt['ber']}\n"
            . "Hisob Frappe'da `Employee` sifatida yaratilishi va sinxron o'tishi kerak.");
    }

    role_assign($rol->id, $user->id, $syscontext->id);
    cli_writeln('  + ' . TZP_ROL . ' (tizim)');

    // Markaziy kurs bo'lsa — darhol editingteacher (sinxonni kutmaslik uchun;
    // Frappe sinxoni baribir shuni ta'minlab turadi).
    $kurs = psixolog::kurs();
    if ($kurs) {
        $etid = (int) $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        if ($etid && enrol_try_internal_enrol((int) $kurs->id, (int) $user->id, $etid)) {
            cli_writeln("  + editingteacher: {$kurs->fullname} ({$kurs->idnumber})");
        } else {
            cli_writeln("  ! markaziy kursga yozib bo'lmadi (manual enrol o'chiqmi?)");
        }
    } else {
        cli_writeln("  ! markaziy kurs (PSIXOLOGIYA::<yil>) hali yo'q — Frappe");
        cli_writeln("    sinxoni (moodle_psixologiya.sync_now) uni yaratadi va yozadi.");
    }
    cli_writeln('Rol berildi: ' . fullname($user) . " ({$user->idnumber})");
}

// ----------------------------------------------------------------- TEKSHIR
if ($opt['tekshir'] !== '') {
    $user = $DB->get_record('user', ['idnumber' => $opt['tekshir'], 'deleted' => 0]);
    if (!$user) {
        cli_error("Bunday idnumber bilan foydalanuvchi topilmadi: {$opt['tekshir']}");
    }
    cli_writeln(fullname($user) . " ({$user->idnumber})");
    cli_writeln('');

    $rollar = get_user_roles($syscontext, $user->id, false);
    cli_writeln('Tizim kontekstidagi rollar: '
        . ($rollar ? implode(', ', array_map(fn($r) => $r->shortname, $rollar)) : "YO'Q"));

    cli_writeln('');
    cli_writeln('--- TIZIM konteksti');
    foreach (TZP_RUXSATLAR as $cap) {
        cli_writeln('  ' . (has_capability($cap, $syscontext, $user)
            ? '[bor]    ' : "[yo'q]   ") . $cap);
    }

    $kurs = psixolog::kurs();
    if ($kurs) {
        $kc = context_course::instance((int) $kurs->id);
        cli_writeln('');
        cli_writeln('--- MARKAZIY KURS: ' . format_string($kurs->fullname)
            . " ({$kurs->idnumber})");
        foreach (['moodle/course:manageactivities', 'mod/quiz:addinstance',
                  'mod/feedback:addinstance', 'mod/quiz:viewreports',
                  'mod/feedback:viewreports'] as $cap) {
            cli_writeln('  ' . (has_capability($cap, $kc, $user)
                ? '[bor]    ' : "[yo'q]   ") . $cap);
        }
    } else {
        cli_writeln('');
        cli_writeln("! Markaziy kurs (PSIXOLOGIYA::<yil>) topilmadi — Frappe sinxoni o'tmagan.");
    }

    cli_writeln('');
    cli_writeln("`viewteachers`, `viewallpayroll` va `site:config` YO'Q bo'lishi TO'G'RI:");
    cli_writeln("psixolog direktor panelini, oyliklarni va sayt sozlamalarini ko'rmaydi.");
    foreach (['local/targetzenit:viewteachers', 'local/targetzenit:viewallpayroll',
              'moodle/site:config'] as $cap) {
        cli_writeln('  ' . (has_capability($cap, $syscontext, $user)
            ? '[BOR!]   ' : "[yo'q]   ") . $cap);
    }
}

// ------------------------------------------------------------------- BERGAN
if ($opt['bergan']) {
    $rol = $DB->get_record('role', ['shortname' => TZP_ROL]);
    cli_writeln('--- ' . TZP_ROL);
    if (!$rol) {
        cli_writeln('  rol hali yaratilmagan');
        exit(0);
    }
    $rows = $DB->get_records_sql("
        SELECT ra.id, u.firstname, u.lastname, u.idnumber, u.email
          FROM {role_assignments} ra
          JOIN {user} u ON u.id = ra.userid
         WHERE ra.roleid = :rid AND u.deleted = 0
      ORDER BY u.lastname", ['rid' => $rol->id]);
    if (!$rows) {
        cli_writeln('  rol hech kimga berilmagan');
    }
    foreach ($rows as $u) {
        cli_writeln('  ' . str_pad(fullname($u), 32)
            . str_pad((string) $u->idnumber, 18) . $u->email);
    }
}
