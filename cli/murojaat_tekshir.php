<?php
/**
 * Murojaat modulining SOG'LIGINI tekshiradi — "Error reading from database"
 * kabi umumiy xabar ortida AYNAN qaysi so'rov yiqilayotganini ko'rsatadi.
 *
 *   php local/targetzenit/cli/murojaat_tekshir.php
 *   php local/targetzenit/cli/murojaat_tekshir.php --kim=HR-EMP-00113
 *
 * NEGA KERAK: Moodle sahifada DB xatosini yashiradi (debugging o'chiq bo'lsa
 * faqat "Error reading from database" chiqadi). Bu vosita har so'rovni
 * alohida yurgizadi va xato matnini to'liq beradi.
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_targetzenit\murojaat;

[$opt, $un] = cli_get_params(['help' => false, 'kim' => ''], ['h' => 'help']);

if ($opt['help']) {
    cli_writeln("--kim=<idnumber>   shu xodim nomidan tekshiradi (ruxsat ham sinaladi)");
    exit(0);
}

$context = context_system::instance();

// --- 1. Jadvallar
cli_writeln('== Jadvallar ==');
$dbman = $DB->get_manager();
$yoq = 0;
foreach (['local_targetzenit_murojaat',
          'local_targetzenit_murojaat_izoh',
          'local_targetzenit_murojaat_suhbat'] as $nom) {
    $bor = $dbman->table_exists(new xmldb_table($nom));
    $soni = $bor ? $DB->count_records($nom) : 0;
    $seq = '';
    if ($bor) {
        $ust = $DB->get_columns($nom, false);
        $seq = empty($ust['id']->auto_increment) ? '  !! id AUTO_INCREMENT EMAS' : '';
    }
    cli_writeln(sprintf('  %-36s %s  %s qator%s', $nom,
        $bor ? 'bor' : 'YO\'Q', $bor ? $soni : '-', $seq));
    if (!$bor) {
        $yoq++;
    }
}
if ($yoq) {
    cli_writeln('  -> Jadval yetishmayapti: admin/cli/upgrade.php ishlatilganmi?');
}

// --- 2. Foydalanuvchi va ruxsat
if ($opt['kim'] !== '') {
    $user = $DB->get_record('user', ['idnumber' => $opt['kim'], 'deleted' => 0]);
    if (!$user) {
        cli_error("Bunday idnumber bilan foydalanuvchi yo'q: {$opt['kim']}");
    }
    \core\session\manager::set_user($user);
    cli_writeln('');
    cli_writeln('== Foydalanuvchi: ' . fullname($user) . ' ==');
}
cli_writeln('');
cli_writeln('== Ruxsat ==');
$yonalishlar = murojaat::yonalishlar($context);
cli_writeln('  yo\'nalishlar: ' . ($yonalishlar ? implode(', ', $yonalishlar) : 'YO\'Q'));
if (!$yonalishlar) {
    cli_writeln('  -> Sahifa "ruxsat yo\'q" deydi. murojaat_rol.php --ber bilan rol bering.');
}

// --- 3. So'rovlar — har biri ALOHIDA, xato matni bilan
cli_writeln('');
cli_writeln('== So\'rovlar ==');

/** Bitta so'rovni yurgizib, natija yoki xato matnini chiqaradi. */
function tz_sinov(string $nom, callable $fn): void {
    try {
        $natija = $fn();
        $tavsif = is_array($natija) ? count($natija) . ' qator'
            : (is_scalar($natija) ? (string) $natija : gettype($natija));
        cli_writeln(sprintf('  OK    %-28s %s', $nom, $tavsif));
    } catch (Throwable $e) {
        cli_writeln(sprintf('  XATO  %-28s %s', $nom, $e->getMessage()));
        if (!empty($e->debuginfo)) {
            cli_writeln('        ' . str_replace("\n", "\n        ", trim($e->debuginfo)));
        }
    }
}

tz_sinov('stats()', fn() => murojaat::stats());
tz_sinov('kesim()', fn() => murojaat::kesim());
tz_sinov('masullar()', fn() => murojaat::masullar());
tz_sinov('kategoriyalar()', fn() => murojaat::kategoriyalar());
tz_sinov('royxat(ochiq)', fn() => murojaat::royxat('ochiq'));
tz_sinov('royxat(hammasi)', fn() => murojaat::royxat(''));
tz_sinov('royxat(muddati)', fn() => murojaat::royxat('muddati'));
tz_sinov('royxat(menda)', fn() => murojaat::royxat('menda'));
tz_sinov('royxat(qidiruv)', fn() => murojaat::royxat('', 'a'));

// Bitta murojaat ustidagi so'rovlar
$m = $DB->get_record('local_targetzenit_murojaat', [], '*', IGNORE_MULTIPLE);
if ($m) {
    cli_writeln('');
    cli_writeln('== Bitta murojaat: ' . $m->frappename . ' ==');
    tz_sinov('izohlar()', fn() => murojaat::izohlar((int) $m->id));
    tz_sinov('suhbatlar()', fn() => murojaat::suhbatlar((int) $m->id));
    tz_sinov('boshqalari()', fn() => murojaat::boshqalari($m));
    tz_sinov('muddat()', fn() => implode('/', murojaat::muddat($m)));
    tz_sinov('ism(masul)', fn() => murojaat::ism((int) $m->masuluserid));
} else {
    cli_writeln('');
    cli_writeln('Bazada murojaat yo\'q — Frappe\'dan sinxron o\'tganmi?');
    cli_writeln('  bench --site <sayt> execute target_zenit.integrations.moodle_murojaat.sync_now');
}

cli_writeln('');
cli_writeln('Tugadi.');
