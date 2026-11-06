<?php
/**
 * Murojaat rollari — `CEO` roli va direktorga murojaat ruxsati.
 *
 *   php local/targetzenit/cli/murojaat_rol.php --yarat
 *   php local/targetzenit/cli/murojaat_rol.php --qidir=Alisher
 *   php local/targetzenit/cli/murojaat_rol.php --ber=HR-EMP-00001 --rol=ceo
 *   php local/targetzenit/cli/murojaat_rol.php --bergan
 *   php local/targetzenit/cli/murojaat_rol.php --tekshir=HR-EMP-00001
 *
 * NEGA PLAGIN O'ZI YARATMAYDI (direktor_rol.php dagi bilan bir xil qoida):
 * Moodle'da rol yaratish ma'murning qarori. Migratsiyada avtomatik yaratish
 * jim yiqilishi mumkin, va rol sozlamalari qo'lda o'zgartirilsa har deployda
 * qaytib ketardi.
 *
 * IKKI YO'NALISH ALOHIDA (MUROJAAT_ARXITEKTURA.md 4-bo'lim):
 *   CEO      -> tashkiliy va to'lov masalalari
 *   Direktor -> o'quv-tarbiya masalalari (zavuch ham shu rol bilan yuradi)
 * Shuning uchun direktorga YANGI rol yaratilmaydi: mavjud `tzdirektor`
 * roliga faqat murojaat ruxsati qo'shiladi.
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/lib/accesslib.php');

const TZ_CEO_ROL = 'tzceo';
const TZ_DIR_ROL = 'tzdirektor';          // direktor_rol.php yaratadigan rol
const TZ_CEO_CAP = 'local/targetzenit:murojaatceo';
const TZ_DIR_CAP = 'local/targetzenit:murojaatdirektor';

// CEO odamlarni ko'rishi kerak (murojaat kartasida ism va profil)
const TZ_CEO_QOSHIMCHA = ['moodle/site:viewparticipants', 'moodle/user:viewdetails'];

[$opt, $un] = cli_get_params(
    ['help' => false, 'yarat' => false, 'bergan' => false, 'ber' => '',
     'rol' => 'ceo', 'qidir' => '', 'tekshir' => ''],
    ['h' => 'help']
);

if ($opt['help'] || (!$opt['yarat'] && !$opt['bergan'] && $opt['ber'] === ''
        && $opt['qidir'] === '' && $opt['tekshir'] === '')) {
    cli_writeln("--yarat                      `CEO` rolini yaratadi + direktor roliga murojaat ruxsatini qo'shadi");
    cli_writeln("--qidir=<matn>               xodimni topadi va Employee ID sini ko'rsatadi");
    cli_writeln("--ber=<idnumber> --rol=ceo   CEO rolini shu xodimga beradi");
    cli_writeln("--ber=<idnumber> --rol=dir   Direktor rolini shu xodimga beradi");
    cli_writeln("--bergan                     rollar kimga berilganini ko'rsatadi");
    cli_writeln("--tekshir=<idnumber>         shu odamda murojaat ruxsati BOR-YO'Qligini ko'rsatadi");
    exit(0);
}

$context = context_system::instance();

/** Ruxsatni rolga beradi, ruxsat topilmasa ogohlantiradi. */
function tz_cap(string $cap, int $rolid, int $ctxid): void {
    if (!get_capability_info($cap, false)) {
        cli_writeln("  ! ruxsat topilmadi: {$cap} — plagin yangilanganmi? (Purge caches)");
        return;
    }
    assign_capability($cap, CAP_ALLOW, $rolid, $ctxid, true);
    cli_writeln("  + {$cap}");
}

// -------------------------------------------------------------------- YARAT
if ($opt['yarat']) {
    // --- CEO roli
    $ceo = $DB->get_record('role', ['shortname' => TZ_CEO_ROL]);
    if (!$ceo) {
        $rolid = create_role('CEO', TZ_CEO_ROL,
            "Ota-onalarning tashkiliy va to'lov masalalari bo'yicha murojaatlarini "
            . "ko'radi va hal qiladi.", '');
        $ceo = $DB->get_record('role', ['id' => $rolid]);
        cli_writeln("CEO roli yaratildi: id={$rolid}");
    } else {
        cli_writeln("CEO roli allaqachon bor: id={$ceo->id} — ruxsatlari tiklanadi");
    }
    // Rol faqat TIZIM kontekstida beriladi (ruxsat CONTEXT_SYSTEM darajasida)
    set_role_contextlevels($ceo->id, [CONTEXT_SYSTEM]);
    tz_cap(TZ_CEO_CAP, (int) $ceo->id, (int) $context->id);
    foreach (TZ_CEO_QOSHIMCHA as $cap) {
        tz_cap($cap, (int) $ceo->id, (int) $context->id);
    }

    // --- Direktor roliga murojaat ruxsati (yangi rol YARATILMAYDI)
    $dir = $DB->get_record('role', ['shortname' => TZ_DIR_ROL]);
    if (!$dir) {
        cli_writeln('');
        cli_writeln("Direktor roli (" . TZ_DIR_ROL . ") yo'q — avval:");
        cli_writeln("  php local/targetzenit/cli/direktor_rol.php --yarat");
    } else {
        cli_writeln("Direktor roli: id={$dir->id}");
        tz_cap(TZ_DIR_CAP, (int) $dir->id, (int) $context->id);
    }

    $context->mark_dirty();
    cli_writeln('');
    cli_writeln('Tayyor. Endi odamga berish: --ber=<Employee ID> --rol=ceo|dir');
}

// -------------------------------------------------------------------- QIDIR
// Employee ID ni qo'lda qidirib yurmaslik uchun. Rol berish BARIBIR
// `idnumber` bilan ketadi — ism bilan hech qachon (unikal ID qoidasi).
if ($opt['qidir'] !== '') {
    $q = '%' . $DB->sql_like_escape($opt['qidir']) . '%';
    $rows = $DB->get_records_select('user',
        "deleted = 0 AND idnumber <> '' AND ("
        . $DB->sql_like('firstname', ':q1', false) . ' OR '
        . $DB->sql_like('lastname', ':q2', false) . ' OR '
        . $DB->sql_like('idnumber', ':q3', false) . ' OR '
        . $DB->sql_like('email', ':q4', false) . ')',
        ['q1' => $q, 'q2' => $q, 'q3' => $q, 'q4' => $q],
        'lastname ASC', 'id, firstname, lastname, idnumber, email', 0, 30);

    if (!$rows) {
        cli_writeln("Topilmadi. Xodim Frappe'da `Employee` bo'lib, sinxron o'tganmi?");
    }
    foreach ($rows as $u) {
        cli_writeln('  ' . str_pad(fullname($u), 32) . str_pad((string) $u->idnumber, 18)
            . $u->email);
    }
}

// ---------------------------------------------------------------------- BER
if ($opt['ber'] !== '') {
    $qisqa = $opt['rol'] === 'dir' ? TZ_DIR_ROL : TZ_CEO_ROL;
    $rol = $DB->get_record('role', ['shortname' => $qisqa]);
    if (!$rol) {
        cli_error("Rol yo'q ({$qisqa}) — avval --yarat");
    }
    // QOIDA: odam Employee ID (user.idnumber) bo'yicha topiladi, ism bilan
    // HECH QACHON. Hisob Frappe'da yaratilib sinxronda keladi.
    $user = $DB->get_record('user', ['idnumber' => $opt['ber'], 'deleted' => 0]);
    if (!$user) {
        cli_error("Bunday idnumber bilan foydalanuvchi topilmadi: {$opt['ber']}\n"
            . "Hisob Frappe'da `Employee` sifatida yaratilishi va sinxron o'tishi kerak.");
    }
    role_assign($rol->id, $user->id, $context->id);
    cli_writeln("Rol berildi: {$rol->shortname} -> " . fullname($user) . " ({$user->idnumber})");
}

// ------------------------------------------------------------------- BERGAN
if ($opt['bergan']) {
    foreach ([TZ_CEO_ROL, TZ_DIR_ROL] as $qisqa) {
        $rol = $DB->get_record('role', ['shortname' => $qisqa]);
        cli_writeln('');
        if (!$rol) {
            cli_writeln("{$qisqa}: rol yo'q");
            continue;
        }
        $odamlar = get_role_users($rol->id, $context);
        cli_writeln("{$qisqa} ({$rol->name}): " . count($odamlar) . ' kishi');
        foreach ($odamlar as $u) {
            cli_writeln('  - ' . fullname($u) . ' (' . ($u->idnumber ?: '—') . ')');
        }
    }
}

// ----------------------------------------------------------------- TEKSHIR
// "Panel bo'sh" muammosi ruxsatdanmi yoki ma'lumotdanmi — shuni aniqlaydi.
if ($opt['tekshir'] !== '') {
    $user = $DB->get_record('user', ['idnumber' => $opt['tekshir'], 'deleted' => 0]);
    if (!$user) {
        cli_error("Bunday idnumber bilan foydalanuvchi topilmadi: {$opt['tekshir']}");
    }
    cli_writeln(fullname($user) . " ({$user->idnumber})");
    cli_writeln('');
    foreach ([TZ_CEO_CAP => 'CEO murojaatlari', TZ_DIR_CAP => 'Direktor murojaatlari'] as $cap => $nom) {
        $bor = has_capability($cap, $context, $user->id);
        cli_writeln('  ' . ($bor ? '✓' : '✗') . "  {$nom}  ({$cap})");
    }
    cli_writeln('');
    $soni = $DB->count_records('local_targetzenit_murojaat');
    cli_writeln("Bazadagi murojaatlar: {$soni} ta");
    if (!$soni) {
        cli_writeln("  Frappe'dan sinxron o'tmagan. Frappe serverida:");
        cli_writeln("  bench --site <sayt> execute target_zenit.integrations.moodle_murojaat.sync_now");
    }
}
