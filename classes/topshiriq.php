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
	 * @param string $year o'quv yili (`2026-2027`) — kurslar `<yil>::%`
	 * @param array|null $courseids faqat shu kurslar (o'qituvchi uchun);
	 *                              null — yildagi hamma kurs
	 * @return array har element: cmid, name, duedate, visible, courseid,
	 *               coursename, guruh, fan, topshirgan, qoralama, baholangan,
	 *               jami, uyvazifa (bool)
	 */
	public static function royxat(string $year, ?array $courseids = null): array {
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
		} else {
			$where = 'c.idnumber LIKE :p';
			$params['p'] = $DB->sql_like_escape($year) . '::%';
		}

		// Sanoqlar subquery'da: har topshiriq uchun alohida so'rov qilsak
		// 300 topshiriq = 900 so'rov bo'lardi.
		//
		// `latest = 1` — Moodle bir o'quvchining bir necha urinishini
		// saqlaydi, bizga oxirgisi kerak. `grade >= 0` — baholanmagan
		// qator `-1` bilan yoziladi, `IS NOT NULL` yolg'iz yetmaydi.
		$sql = "SELECT cm.id AS cmid, a.id AS assignid, a.name, a.duedate,
					   a.allowsubmissionsfromdate, a.submissiondrafts,
					   cm.visible, c.id AS courseid, c.fullname AS coursename,
					   c.idnumber,
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
