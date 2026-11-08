<?php
// Copyright (c) 2026, Target Zenit
//
// «MAVZU 0 DARSGA BIRIKTIRILDI» — TASHXIS.
//
// Biriktirish uchun UCH narsa bir-biriga mos kelishi kerak:
//   1) `_mavzu`  — Frappe yuborgan reja (subjectkey, groupkey, chorak)
//   2) `_calendar` entrytype=term — choraklarning SANA oralig'i
//   3) `_slot`   — dars jadvali (subjectkey, groupkey, lessondate)
// Bittasi yetishmasa natija jim-jimlikda 0 bo'ladi. Bu skript qaysi bo'g'in
// uzilganini ko'rsatadi.
//
// Ishlatilishi (root/www-data bo'lib):
//   php /opt/moodle/public/local/targetzenit/cli/mavzu_tekshir.php
//   php ... --yil=2026-2027 --biriktir     (tekshirgandan keyin qayta biriktiradi)

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options] = cli_get_params(
	['help' => false, 'yil' => '', 'biriktir' => false],
	['h' => 'help']
);

if ($options['help']) {
	cli_writeln("Mavzu biriktirish tashxisi.\n");
	cli_writeln("  --yil=2026-2027  o'quv yili (bo'sh bo'lsa `_mavzu` dagi eng ko'p uchragani)");
	cli_writeln("  --biriktir       tekshirgandan keyin qayta biriktiradi");
	exit(0);
}

$yil = trim($options['yil']);
if ($yil === '') {
	// DIQQAT: Moodle DML'da LIMIT uchun alohida metod yo'q — chegara
	// `get_records_sql` ning 3- va 4- parametri orqali beriladi.
	$eng = $DB->get_records_sql(
		"SELECT academicyear, COUNT(*) AS n FROM {local_targetzenit_mavzu}
		  GROUP BY academicyear ORDER BY COUNT(*) DESC", null, 0, 1);
	$yil = $eng ? reset($eng)->academicyear : '';
}
if (!$yil) {
	cli_problem("`_mavzu` jadvali BO'SH — Frappe hali mavzu yubormagan.");
	cli_writeln("  Frappe: bench --site <sayt> execute "
		. "target_zenit.integrations.moodle_mavzu.push");
	exit(1);
}

cli_heading("Mavzu tashxisi — o'quv yili: $yil");

// ---- 1) Reja (Frappe yuborgan)
$mavzu_jami = $DB->count_records('local_targetzenit_mavzu', ['academicyear' => $yil]);
cli_writeln("1) REJA (`_mavzu`): $mavzu_jami qator");
$choraklar_mavzuda = $DB->get_records_sql(
	"SELECT chorak, COUNT(*) AS n FROM {local_targetzenit_mavzu}
	  WHERE academicyear = :y GROUP BY chorak ORDER BY chorak", ['y' => $yil]);
foreach ($choraklar_mavzuda as $c) {
	cli_writeln(sprintf('     chorak %-3s %d mavzu', $c->chorak ?: '(yo\'q)', $c->n));
}

// ---- 2) Kalendar choraklari
$choraklar = \local_targetzenit\mavzu::choraklar($yil);
cli_writeln('');
cli_writeln('2) CHORAKLAR (`_calendar` entrytype=term): ' . count($choraklar));
if (!$choraklar) {
	cli_problem('     KALENDAR BO\'SH — chorak sanalari yo\'q, shuning uchun');
	cli_writeln('     chorakli mavzularning HAMMASI «sig\'madi» bo\'lib qoladi.');
	cli_writeln('     Yechim: Frappe\'dan jadval+kalendarni qayta yuboring:');
	cli_writeln('       bench --site <sayt> execute '
		. 'target_zenit.integrations.moodle_jadval.push_now');
} else {
	foreach ($choraklar as $r => $c) {
		cli_writeln(sprintf('     %d-chorak  %s .. %s   «%s»',
			$r, $c['boshi'], $c['oxiri'], $c['nomi']));
	}
}

// ---- 3) Juftliklar: rejada bor, jadvalda bormi
cli_writeln('');
cli_writeln('3) JUFTLIKLAR (fan / guruh) — rejada bor, jadvalda ham bormi:');
// DIQQAT: `get_records_sql` natijani BIRINCHI ustun bo'yicha kalitlaydi —
// GROUP BY da subjectkey takrorlansa qatorlar jim yo'qoladi.
$juftlar = $DB->get_recordset_sql(
	"SELECT subjectkey, groupkey, COUNT(*) AS n FROM {local_targetzenit_mavzu}
	  WHERE academicyear = :y GROUP BY subjectkey, groupkey
	  ORDER BY subjectkey, groupkey", ['y' => $yil]);

$muammo = 0;
foreach ($juftlar as $j) {
	$slot = $DB->count_records('local_targetzenit_slot', [
		'academicyear' => $yil, 'subjectkey' => $j->subjectkey, 'groupkey' => $j->groupkey]);
	$biriktirilgan = $DB->count_records_select('local_targetzenit_slot',
		'academicyear = :y AND subjectkey = :f AND groupkey = :g AND mavzuid > 0',
		['y' => $yil, 'f' => $j->subjectkey, 'g' => $j->groupkey]);
	$belgi = $slot ? 'OK' : 'JADVALDA DARS YO\'Q';
	if (!$slot) {
		$muammo++;
	}
	cli_writeln(sprintf('     %-14s %-16s reja:%-4d dars:%-4d biriktirilgan:%-4d  %s',
		$j->subjectkey, $j->groupkey, $j->n, $slot, $biriktirilgan, $belgi));
}
$juftlar->close();

if ($muammo) {
	cli_writeln('');
	cli_problem("     $muammo juftlikda dars jadvali yo'q.");
	cli_writeln('     Sabab: `_slot` dagi subjectkey/groupkey Frappe yuborgan nom bilan');
	cli_writeln('     bir xil bo\'lishi kerak. Jadvalda bor nomlar:');
	$bor = $DB->get_recordset_sql(
		"SELECT subjectkey, groupkey, COUNT(*) AS n FROM {local_targetzenit_slot}
		  WHERE academicyear = :y GROUP BY subjectkey, groupkey
		  ORDER BY COUNT(*) DESC", ['y' => $yil], 0, 15);
	$bormi = false;
	foreach ($bor as $b) {
		$bormi = true;
		cli_writeln(sprintf('       %-14s %-16s %d dars', $b->subjectkey, $b->groupkey, $b->n));
	}
	$bor->close();
	if (!$bormi) {
		cli_writeln('       (hech narsa — `_slot` shu yil uchun bo\'sh, jadval yuborilmagan)');
	}
}

// ---- Xulosa
cli_writeln('');
$slot_jami = $DB->count_records('local_targetzenit_slot', ['academicyear' => $yil]);
$bir_jami = $DB->count_records_select('local_targetzenit_slot',
	'academicyear = :y AND mavzuid > 0', ['y' => $yil]);
cli_heading('Xulosa');
cli_writeln("  Jadvaldagi darslar: $slot_jami");
cli_writeln("  Mavzu biriktirilgan darslar: $bir_jami");

if ($options['biriktir']) {
	cli_writeln('');
	cli_heading('Qayta biriktirish');
	$h = \local_targetzenit\mavzu::biriktir($yil);
	cli_writeln('  juftlar: ' . $h['juftlar']);
	cli_writeln('  biriktirildi: ' . $h['biriktirildi']);
	cli_writeln('  bo\'sh dars (mavzu yetmadi): ' . $h['bosh_slot']);
	cli_writeln('  sig\'madi (dars yetmadi): ' . $h['sigmagan']);
	foreach ($h['ogohlantirish'] as $o) {
		cli_writeln('  ! ' . $o);
	}
}
exit(0);
