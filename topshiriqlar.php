<?php
// Copyright (c) 2026, Target Zenit
//
// TOPSHIRIQLAR (Moodle mod_assign) — bitta ro'yxatda.
//
// Sahifa javob beradigan savol: «qaysi guruhda qanday topshiriq bor, kim
// topshirdi, kim baholandi, qaysi biri qoralamada qolib ketdi?»
//
// NEGA KERAK: Moodle'da topshiriq natijalari faqat kurs ICHIDA ko'rinadi
// (`/mod/assign/view.php?action=grading`), ya'ni koordinator har guruhni
// qo'lda ochib chiqishi kerak edi. Bundan tashqari Moodle 4.3+ da
// «View all submissions» tugmasi tab'ga ko'chgan va uni topish qiyin —
// shu sababli har qatorda TO'G'RIDAN-TO'G'RI havola turadi.
//
// BU SAHIFA BAHO YOZMAYDI: baholash va sozlama Moodle'ning o'z
// sahifalarida qoladi (bitta yozuvchi tamoyili), biz faqat ko'rsatamiz va
// havola beramiz.

require(__DIR__ . '/../../config.php');

use local_targetzenit\output\shell;
use local_targetzenit\output\ui;
use local_targetzenit\timetable;
use local_targetzenit\topshiriq;

require_login();

$context = context_system::instance();
$t = fn(string $k, $a = null) => get_string($k, 'local_targetzenit', $a);

// Qamrov: hamma guruhni ko'rish alohida ruxsat talab qiladi. Ruxsati
// yo'q o'qituvchi faqat o'zi baholaydigan kurslarni ko'radi — ya'ni
// sahifa hamma uchun ochiq, lekin KO'RINISH DOIRASI har xil.
$hammasi = has_capability('local/targetzenit:manageexams', $context)
	|| has_capability('local/targetzenit:viewteachers', $context)
	|| has_capability('moodle/site:config', $context);

$year = timetable::current_year();
$courseids = $hammasi ? null : topshiriq::mening_kurslarim($USER->id);

if (!$hammasi && !$courseids) {
	// Na ruxsat, na baholaydigan kurs — bu sahifada ko'rsatadigan narsa yo'q.
	throw new moodle_exception('nopermissions', 'error', '', $t('tp_nav'));
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/targetzenit/topshiriqlar.php'));
shell::setup($PAGE, $t('tp_nav'));

$rows = topshiriq::royxat($year, $courseids);

// Menyu: koordinatorga baholash menyusi, direktorga direktor menyusi,
// qolganiga (fan o'qituvchisi) o'z paneli menyusi.
if (has_capability('local/targetzenit:manageexams', $context)) {
	$menu = shell::bh_menu();
} else if (has_capability('local/targetzenit:viewteachers', $context)) {
	$menu = shell::dir_menu();
} else {
	$menu = shell::oq_menu($USER->id, true);
}

shell::start_menu('topshiriq', $t('tp_nav'), $t('tp_hint'), '', $menu);

if (!$rows) {
	echo html_writer::div($t('tp_empty'), 'tz-dir-empty');
	shell::end();
	exit;
}

// --- Umumiy hisob (qoralama alohida: aynan shu «topshirilmagan» holat
// o'qituvchini chalg'itadi — baho qo'yilgan, lekin o'quvchi yubormagan).
$jamiq = array_sum(array_map(fn($r) => $r->qoralama, $rows));
$jamib = array_sum(array_map(fn($r) => $r->baholangan, $rows));
$jamit = array_sum(array_map(fn($r) => $r->topshirgan, $rows));

echo html_writer::start_div('tz-dir-hero');
echo ui::tile((string) count($rows), $t('tp_total'));
echo ui::tile((string) $jamit, $t('tp_submitted'));
echo ui::tile((string) $jamib, $t('tp_graded'));
echo ui::tile((string) $jamiq, $t('tp_draft'), $jamiq ? $t('tp_drafthint') : '');
echo html_writer::end_div();

echo html_writer::start_tag('table', ['class' => 'tz-dir-table']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
foreach (['tp_group', 'tp_name', 'tp_due', 'tp_progress', 'tp_actions'] as $k) {
	echo html_writer::tag('th', $t($k));
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

foreach ($rows as $r) {
	echo html_writer::start_tag('tr');

	// --- guruh + fan
	$guruh = html_writer::tag('span', s($r->guruh), ['class' => 'tz-dir-chipname']);
	if ($r->fan !== '') {
		$guruh .= html_writer::tag('span', s($r->fan), ['class' => 'tz-dir-sub']);
	}
	echo html_writer::tag('td', $guruh);

	// --- nomi + belgilar
	$nom = html_writer::link(topshiriq::baholash_url($r->cmid), s($r->name));
	$belgi = '';
	if (!$r->visible) {
		// Yashirin faoliyat: o'quvchi uni umuman ko'rmaydi. Bu «draft»
		// deb chalkashtiriladigan ikkinchi holat.
		$belgi .= ' ' . ui::chip('critical', $t('tp_hidden'), $t('tp_hiddenhint'));
	}
	if ($r->uyvazifa) {
		// Dars kokpiti yaratgan topshiriq: muddatni Moodle'da
		// o'zgartirish BEFOYDA — `homework::ensure()` qayta yozadi.
		$belgi .= ' ' . ui::chip('none', $t('tp_hw'), $t('tp_hwhint'));
	}
	echo html_writer::tag('td', $nom . $belgi);

	// --- muddat
	$muddat = $r->duedate
		? userdate($r->duedate, get_string('strftimedatetimeshort', 'core_langconfig'))
		: $t('tp_nodue');
	echo html_writer::tag('td', s($muddat), ['class' => 'tz-tp-due']);

	// --- holat: topshirgan / jami, qoralama, baholangan
	$h = ui::chip($r->topshirgan ? 'good' : 'none',
		$t('tp_subshort') . ': ' . $r->topshirgan . '/' . $r->jami);
	if ($r->qoralama) {
		$h .= ' ' . ui::chip('warning', $t('tp_draftshort') . ': ' . $r->qoralama,
			$t('tp_drafthint'));
	}
	$h .= ' ' . ui::chip($r->baholangan ? 'good' : 'none',
		$t('tp_gradeshort') . ': ' . $r->baholangan);
	echo html_writer::tag('td', $h);

	// --- amallar: ikkisi ham Moodle yadrosining sahifasi
	$amal = html_writer::link(topshiriq::baholash_url($r->cmid), $t('tp_grade'),
		['class' => 'tz-tp-btn']);
	if (has_capability('moodle/course:manageactivities',
			context_module::instance($r->cmid))) {
		$amal .= ' ' . html_writer::link(topshiriq::sozlama_url($r->cmid),
			$t('tp_settings'), ['class' => 'tz-tp-btn']);
	}
	echo html_writer::tag('td', $amal);

	echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');
shell::end();
