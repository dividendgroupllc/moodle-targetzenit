<?php
/**
 * `Direktor` rolini yaratish — BIR MARTALIK vosita.
 *
 *   php local/targetzenit/cli/direktor_rol.php --yarat
 *   php local/targetzenit/cli/direktor_rol.php --bergan        # kimda bor
 *   php local/targetzenit/cli/direktor_rol.php --ber=HR-EMP-00001
 *
 * NEGA PLAGIN O'ZI YARATMAYDI: Moodle'da rol yaratish ma'murning qarori.
 * Migratsiyada avtomatik yaratish jim yiqilishi mumkin va keyin rol
 * sozlamalari qo'lda o'zgartirilsa, har deployda qaytib ketardi.
 *
 * Qamrov: zavuch ham SHU rol bilan yuradi — cheklanmaydi (qaror 2026-10-29).
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/lib/accesslib.php');

const TZ_ROL = 'tzdirektor';
const TZ_RUXSATLAR = [
    'local/targetzenit:viewteachers',
    'local/targetzenit:givefeedback',
    'local/targetzenit:viewprivatefeedback',
    'local/targetzenit:viewanalysis',
    'local/targetzenit:viewalltimetable',
];

[$opt, $un] = cli_get_params(
    ['help' => false, 'yarat' => false, 'bergan' => false, 'ber' => ''],
    ['h' => 'help']
);

if ($opt['help'] || (!$opt['yarat'] && !$opt['bergan'] && $opt['ber'] === '')) {
    cli_writeln("--yarat              `Direktor` rolini yaratadi (bor bo'lsa ruxsatlarini tiklaydi)");
    cli_writeln("--bergan             rol kimga berilganini ko'rsatadi");
    cli_writeln("--ber=<idnumber>     rolni shu xodimga beradi (Employee ID bo'yicha)");
    exit(0);
}

$context = context_system::instance();

// -------------------------------------------------------------------- YARAT
if ($opt['yarat']) {
    $rol = $DB->get_record('role', ['shortname' => TZ_ROL]);
    if (!$rol) {
        $rolid = create_role('Direktor', TZ_ROL,
            "Butun maktab dars jarayonini kuzatadi va fidbek beradi. "
            . "Zavuch ham shu rol bilan yuradi.", 'manager');
        $rol = $DB->get_record('role', ['id' => $rolid]);
        cli_writeln("Rol yaratildi: id={$rolid}");
    } else {
        cli_writeln("Rol allaqachon bor: id={$rol->id} — ruxsatlari tiklanadi");
    }

    // Rol faqat TIZIM kontekstida beriladi
    set_role_contextlevels($rol->id, [CONTEXT_SYSTEM]);

    foreach (TZ_RUXSATLAR as $cap) {
        if (!get_capability_info($cap, false)) {
            cli_writeln("  ! ruxsat topilmadi: {$cap} — plagin yangilanganmi?");
            continue;
        }
        assign_capability($cap, CAP_ALLOW, $rol->id, $context->id, true);
        cli_writeln("  + {$cap}");
    }
    // Direktor odamlarni ko'rishi kerak (ism, profil)
    foreach (['moodle/site:viewparticipants', 'moodle/user:viewdetails'] as $cap) {
        if (get_capability_info($cap, false)) {
            assign_capability($cap, CAP_ALLOW, $rol->id, $context->id, true);
            cli_writeln("  + {$cap}");
        }
    }

    $context->mark_dirty();
    cli_writeln('Tayyor. Rolni berish: --ber=<Employee ID>');
}

// ---------------------------------------------------------------------- BER
if ($opt['ber'] !== '') {
    $rol = $DB->get_record('role', ['shortname' => TZ_ROL]);
    if (!$rol) {
        cli_error("Rol yo'q — avval --yarat");
    }
    // QOIDA: odam Employee ID (user.idnumber) bo'yicha topiladi, ism bilan
    // HECH QACHON. Hisob Frappe'da yaratilib 07:00 sinxronida keladi.
    $user = $DB->get_record('user', ['idnumber' => $opt['ber'], 'deleted' => 0]);
    if (!$user) {
        cli_error("Bunday idnumber bilan foydalanuvchi topilmadi: {$opt['ber']}\n"
            . "Hisob Frappe'da `Employee` sifatida yaratilishi va sinxron o'tishi kerak.");
    }
    role_assign($rol->id, $user->id, $context->id);
    cli_writeln("Rol berildi: " . fullname($user) . " ({$user->idnumber})");
}

// ------------------------------------------------------------------- BERGAN
if ($opt['bergan']) {
    $rol = $DB->get_record('role', ['shortname' => TZ_ROL]);
    if (!$rol) {
        cli_writeln("Rol hali yaratilmagan.");
        exit(0);
    }
    $rows = $DB->get_records_sql("
        SELECT u.id, u.firstname, u.lastname, u.idnumber, u.email
          FROM {role_assignments} ra
          JOIN {user} u ON u.id = ra.userid
         WHERE ra.roleid = :rid AND ra.contextid = :cid AND u.deleted = 0
      ORDER BY u.lastname", ['rid' => $rol->id, 'cid' => $context->id]);

    if (!$rows) {
        cli_writeln("Rol hech kimga berilmagan.");
    }
    foreach ($rows as $u) {
        cli_writeln('  ' . str_pad(fullname($u), 32) . str_pad((string) $u->idnumber, 18)
            . $u->email);
    }
}
