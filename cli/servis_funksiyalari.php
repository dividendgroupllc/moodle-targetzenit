<?php
// Copyright (c) 2026, Target Zenit
//
// FRAPPE SERVISIGA FUNKSIYALARNI BIRIKTIRISH + TEKSHIRISH.
//
// Ishlatilishi (SERVERDA, root bo'lib — frappe foydalanuvchisi config.php ni
// o'qiy olmaydi):
//     sudo -u www-data php /opt/moodle/public/local/targetzenit/cli/servis_funksiyalari.php
//     ... yoki oddiy: php .../servis_funksiyalari.php
//
// `--korish` — hech narsa yozmaydi, faqat holatni ko'rsatadi.

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
	['help' => false, 'korish' => false],
	['h' => 'help']
);

if ($options['help']) {
	cli_writeln("Frappe web service funksiyalarini servisga biriktiradi.\n");
	cli_writeln("  --korish   faqat holatni ko'rsatadi, o'zgartirmaydi");
	cli_writeln("  -h, --help bu yordam");
	exit(0);
}

$kerak = \local_targetzenit\servis::funksiyalar();

cli_heading('Frappe servis funksiyalari');
cli_writeln('Kodda e\'lon qilingan: ' . count($kerak));

$servisid = $DB->get_field('external_services_functions', 'externalserviceid',
	['functionname' => \local_targetzenit\servis::TAYANCH], IGNORE_MULTIPLE);

if (!$servisid) {
	cli_problem("Frappe servisi TOPILMADI.");
	cli_writeln("  «" . \local_targetzenit\servis::TAYANCH . "» hech qaysi servisda yo'q.");
	cli_writeln("  Admin UI: Site administration -> Server -> Web services ->");
	cli_writeln("            External services -> (Frappe servisi) -> Functions");
	exit(1);
}

$servis = $DB->get_record('external_services', ['id' => $servisid]);
cli_writeln('Servis: ' . ($servis->name ?? '?') . ' (' . ($servis->shortname ?? $servisid) . ')');
cli_writeln('Tokenlar: ' . $DB->count_records('external_tokens', ['externalserviceid' => $servisid]));
cli_writeln('');

// Token egasining ruxsati — «accessexception» ning IKKINCHI sababi.
// Funksiya servisda bo'lsa ham, token foydalanuvchisida
// `local/targetzenit:push` bo'lmasa Moodle xuddi shu xatoni beradi.
cli_heading('Token ruxsatlari');
$tokenlar = $DB->get_records('external_tokens', ['externalserviceid' => $servisid]);
if (!$tokenlar) {
	cli_problem('  Bu servisda TOKEN yo\'q — Frappe ulanmaydi.');
} else {
	$tizim = \context_system::instance();
	foreach ($tokenlar as $tk) {
		$u = \core_user::get_user($tk->userid);
		$ism = $u ? fullname($u) . ' (' . $u->username . ')' : 'userid=' . $tk->userid;
		$bormi = has_capability('local/targetzenit:push', $tizim, $tk->userid);
		cli_writeln(sprintf('  %-46s local/targetzenit:push = %s', $ism, $bormi ? 'BOR' : "YO'Q"));
		if (!$bormi) {
			cli_writeln('      -> Bu foydalanuvchiga tizim darajasida shu ruxsatli rol bering');
			cli_writeln('         (Site administration -> Users -> Permissions -> Assign system roles)');
		}
	}
}
cli_writeln('');

cli_heading('Funksiyalar');
$bor = array_flip($DB->get_fieldset_select('external_services_functions', 'functionname',
	'externalserviceid = ?', [$servisid]));

foreach ($kerak as $nom) {
	$royxatda = $DB->record_exists('external_functions', ['name' => $nom]);
	if (isset($bor[$nom])) {
		$holat = 'OK';
	} else if (!$royxatda) {
		$holat = "MOODLE RO'YXATIDA YO'Q (plagin upgrade qilinmagan)";
	} else {
		$holat = 'SERVISDA YO\'Q — biriktirish kerak';
	}
	cli_writeln(sprintf('  %-44s %s', $nom, $holat));
}
cli_writeln('');

if ($options['korish']) {
	cli_writeln("(--korish: hech narsa o'zgartirilmadi)");
	exit(0);
}

$natija = \local_targetzenit\servis::sinxronla(true);
if ($natija['qoshildi']) {
	cli_writeln('');
	cli_writeln('Biriktirildi: ' . count($natija['qoshildi']) . ' funksiya. Frappe push endi ishlaydi.');
} else {
	cli_writeln('Hammasi joyida — qo\'shiladigan funksiya yo\'q.');
}
exit(0);
