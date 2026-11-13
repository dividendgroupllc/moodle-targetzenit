<?php
// Copyright (c) 2026, Target Zenit
//
// MOODLE TOPSHIRIQLARI (mod_assign) ustidan ko'rinish.
//
// NIMA UCHUN KERAK: topshiriq Moodle'ning standart faoliyati, uning
// natijalari faqat kurs ichidagi `/mod/assign/view.php?action=grading`
// sahifasida ko'rinadi. Ya'ni koordinator yoki direktor «qaysi guruhda
// qaysi topshiriq bor, kim topshirdi, kim baholandi?» degan savolga javob
// olish uchun 300 kursni qo'lda aylanib chiqishi kerak edi. Bu klass shu
// savolni BITTA so'rov bilan beradi.
//
// MUHIM: bu yerda baho O'ZGARTIRILMAYDI va topshiriq yaratilmaydi — faqat
// o'qish va Moodle'ning o'z sahifalariga havola. Baholash mantiqi yadroda
// qoladi (bitta yozuvchi tamoyili).

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class topshiriq {

	/**
	 * Topshiriqlar ro'yxati.
	 *
	 * @param string|null $year o'quv yili (`2026-2027`) — kurslar `<yil>::%`.
	 *        null bo'lsa yil bo'yicha FILTRLANMAYDI: Moodle'da qo'lda
	 *        yaratilgan kursda `idnumber` bo'lmaydi va u yil naqshiga
	 *        tushmaydi — shunday kursdagi topshiriq ko'rinmay qolardi.
	 * @param array|null $courseids faqat shu kurslar (o'qituvchi uchun);
	 *                              null — qamrovdagi hamma kurs
	 * @return array har element: cmid, name, duedate, visible, courseid,
	 *               coursename, guruh, fan, topshirgan, qoralama, baholangan,
	 *               jami, uyvazifa (bool)
	 */
	public static function royxat(?string $year, ?array $courseids = null): array {
		global $DB;

		$modid = $DB->get_field('modules', 'id', ['name' => 'assign']);
		if (!$modid) {
			return [];   // mod_assign o'chirilgan
		}

		$params = ['modid' => $modid];
		$where = '';
		if ($courseids !== null) {
			if (!$courseids) {
				return [];
			}
			list($in, $p) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'c');
			$where = "c.id $in";
			$params += $p;
		} else if ($year !== null) {
			// `::%` ataylab ekranlanmaydi — bu naqsh, literal emas.
			$where = 'c.idnumber LIKE :p';
			$params['p'] = $DB->sql_like_escape($year) . '::%';
		} else {
			$where = 'c.id <> :siteid';
			$params['siteid'] = SITEID;
		}

		// Sanoqlar subquery'da: har topshiriq uchun alohida so'rov qilsak
		// 300 topshiriq = 900 so'rov bo'lardi.
		//
		// `latest = 1` — Moodle bir o'quvchining bir necha urinishini
		// saqlaydi, bizga oxirgisi kerak. `grade >= 0` — baholanmagan
		// qator `-1` bilan yoziladi, `IS NOT NULL` yolg'iz yetmaydi.
		$sql = "SELECT cm.id AS cmid, a.id AS assignid, a.name, a.duedate,
					   a.allowsubmissionsfromdate, a.submissiondrafts,
					   a.markingworkflow, a.grade AS bahoturi,
					   cm.visible, c.id AS courseid, c.fullname AS coursename,
					   c.idnumber, c.showgrades, gi.hidden AS itemhidden,
					   (SELECT COUNT(DISTINCT uf.userid) FROM {assign_user_flags} uf
						 WHERE uf.assignment = a.id
						   AND uf.workflowstate = 'released') AS elon,
					   (SELECT COUNT(DISTINCT s.userid) FROM {assign_submission} s
						 WHERE s.assignment = a.id AND s.latest = 1
						   AND s.status = 'submitted') AS topshirgan,
					   (SELECT COUNT(DISTINCT s.userid) FROM {assign_submission} s
						 WHERE s.assignment = a.id AND s.latest = 1
						   AND s.status = 'draft') AS qoralama,
					   (SELECT COUNT(DISTINCT g.userid) FROM {assign_grades} g
						 WHERE g.assignment = a.id AND g.grade IS NOT NULL
						   AND g.grade >= 0) AS baholangan,
					   l.id AS lessonid
				  FROM {assign} a
				  JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = :modid
				  JOIN {course} c ON c.id = cm.course
			 LEFT JOIN {local_targetzenit_lesson} l ON l.assigncmid = cm.id
			 LEFT JOIN {grade_items} gi ON gi.courseid = c.id
					   AND gi.itemtype = 'mod' AND gi.itemmodule = 'assign'
					   AND gi.iteminstance = a.id AND gi.itemnumber = 0
				 WHERE $where
			  ORDER BY a.duedate DESC, c.fullname ASC";

		$rows = $DB->get_records_sql($sql, $params);
		if (!$rows) {
			return [];
		}

		$jami = self::oquvchi_soni(array_unique(array_map(
			fn($r) => (int) $r->courseid, $rows)));

		$out = [];
		foreach ($rows as $r) {
			list($guruh, $fan) = self::guruh_fan((string) $r->idnumber, (string) $r->coursename);
			$out[] = (object) [
				'cmid' => (int) $r->cmid,
				'name' => (string) $r->name,
				'duedate' => (int) $r->duedate,
				'ochilish' => (int) $r->allowsubmissionsfromdate,
				'draftrejim' => (int) $r->submissiondrafts,
				'visible' => (int) $r->visible,
				'courseid' => (int) $r->courseid,
				'coursename' => (string) $r->coursename,
				'guruh' => $guruh,
				'fan' => $fan,
				'topshirgan' => (int) $r->topshirgan,
				'qoralama' => (int) $r->qoralama,
				'baholangan' => (int) $r->baholangan,
				'jami' => (int) ($jami[(int) $r->courseid] ?? 0),
				'uyvazifa' => !empty($r->lessonid),
				'workflow' => (int) $r->markingworkflow,
				'elon' => (int) $r->elon,
				'itemhidden' => (int) $r->itemhidden,
				'showgrades' => (int) $r->showgrades,
				'bahoturi' => (float) $r->bahoturi,
			];
		}
		return $out;
	}

	/**
	 * {kursid: o'quvchi soni} — faqat `student` rolidagilar.
	 *
	 * NEGA ROL BO'YICHA: oddiy `user_enrolments` sanog'i o'qituvchini ham
	 * qo'shadi, ya'ni «18 dan 19 topshirdi» kabi bema'ni raqam chiqardi.
	 */
	protected static function oquvchi_soni(array $courseids): array {
		global $DB;

		if (!$courseids) {
			return [];
		}
		$studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
		if (!$studentrole) {
			return [];
		}
		list($in, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'c');
		$params['rid'] = $studentrole;
		$params['lvl'] = CONTEXT_COURSE;

		$sql = "SELECT c.id, COUNT(DISTINCT ra.userid) AS n
				  FROM {course} c
				  JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :lvl
				  JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.roleid = :rid
				 WHERE c.id $in
			  GROUP BY c.id";
		$rows = $DB->get_records_sql($sql, $params);
		$out = [];
		foreach ($rows as $r) {
			$out[(int) $r->id] = (int) $r->n;
		}
		return $out;
	}

	/**
	 * `2026-2027::ENGLISH Middle 6-7::ENGLISH` -> ['ENGLISH Middle 6-7', 'ENGLISH'].
	 *
	 * Kurs idnumber'i Frappe tomonidan shu ko'rinishda yoziladi
	 * (`moodle_kurs.py`). Qo'lda yaratilgan kursda idnumber bo'lmaydi —
	 * unda to'liq nom qaytariladi.
	 */
	protected static function guruh_fan(string $idnumber, string $coursename): array {
		$q = explode('::', $idnumber);
		if (count($q) === 3) {
			return [$q[1], $q[2]];
		}
		return [$coursename, ''];
	}

	/**
	 * Foydalanuvchi baholay oladigan kurslar — o'qituvchi qamrovi uchun.
	 *
	 * `mod/assign:grade` bo'yicha tekshiriladi: o'qituvchi o'zi yozilgan
	 * kursdagi topshiriqlarni baholaydi. Yozilish yolg'iz yetmaydi —
	 * o'quvchi ham kursga yozilgan.
	 */
	public static function mening_kurslarim(int $userid): array {
		$out = [];
		foreach (enrol_get_users_courses($userid, true, 'id') as $c) {
			if (has_capability('mod/assign:grade', \context_course::instance($c->id), $userid)) {
				$out[] = (int) $c->id;
			}
		}
		return $out;
	}

	/**
	 * Qo'yilgan baho O'QUVCHIGA yetib bordimi?
	 *
	 * NIMA UCHUN KERAK: «baho qo'ydim, lekin o'quvchi ko'rmayapti» —
	 * Moodle'da buning TO'RT xil sababi bor va ularning hech biri
	 * baholash ekranida ko'rinmaydi. Shuning uchun ularni shu yerda
	 * bitta javobga yig'amiz.
	 *
	 * Tartib muhim: eng «qattiq» to'siq birinchi aytiladi, aks holda
	 * odam ikkinchi darajali sababni tuzatib, natija o'zgarmaganini
	 * ko'rib chalkashadi.
	 *
	 * @param object $r `royxat()` qatori
	 * @return array [holat, sabab_kaliti] — holat: ok|warning|critical|none
	 */
	public static function korinish(\stdClass $r): array {
		if ($r->bahoturi == 0) {
			// Baholanmaydigan topshiriq — ko'rsatadigan baho yo'q.
			return ['none', 'tp_vis_nograde'];
		}
		if (!$r->showgrades) {
			// Kurs darajasida baholar o'chirilgan — hamma baho yashirin.
			return ['critical', 'tp_vis_nocourse'];
		}
		if ($r->itemhidden) {
			// Gradebook'da shu ustun yashirilgan.
			return ['critical', 'tp_vis_itemhidden'];
		}
		if ($r->workflow && $r->baholangan > $r->elon) {
			// Marking workflow: baho «Released» bo'lmaguncha o'quvchi
			// ko'rmaydi. Eng ko'p uchraydigan va eng ko'rinmas sabab.
			return ['warning', 'tp_vis_notreleased'];
		}
		if (!$r->baholangan) {
			return ['none', 'tp_vis_nomarks'];
		}
		return ['ok', 'tp_vis_ok'];
	}

	/**
	 * Diagnostika: Moodle'da jami nechta topshiriq va kurs bor.
	 *
	 * NEGA KERAK: «topshiriq yo'q» degan bo'sh ekran ikki xil ma'noni
	 * yashiradi — haqiqatan yo'q, yoki bizning qamrov tashqarisida.
	 * Bo'sh holatda shu raqamlar ko'rsatiladi, shunda sababni taxmin
	 * qilish kerak bo'lmaydi.
	 *
	 * @return object jami (assign soni), kurslar (assign bor kurs soni),
	 *                quiz (quiz soni — odam «topshiriq» deb quiz yaratgan
	 *                bo'lishi mumkin)
	 */
	public static function tashxis(): \stdClass {
		global $DB;

		$assign = (int) $DB->count_records('assign');
		$kurslar = (int) $DB->count_records_sql(
			'SELECT COUNT(DISTINCT cm.course) FROM {course_modules} cm
			  JOIN {modules} m ON m.id = cm.module WHERE m.name = ?', ['assign']);
		$quiz = $DB->get_manager()->table_exists('quiz')
			? (int) $DB->count_records('quiz') : 0;

		return (object) ['jami' => $assign, 'kurslar' => $kurslar, 'quiz' => $quiz];
	}

	/** Baholash sahifasi (Moodle yadrosi). */
	public static function baholash_url(int $cmid): \moodle_url {
		return new \moodle_url('/mod/assign/view.php',
			['id' => $cmid, 'action' => 'grading']);
	}

	/** Sozlamalar sahifasi (muddat shu yerda o'zgaradi). */
	public static function sozlama_url(int $cmid): \moodle_url {
		return new \moodle_url('/course/modedit.php', ['update' => $cmid, 'return' => 1]);
	}
}
