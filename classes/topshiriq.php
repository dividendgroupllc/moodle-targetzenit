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
		$out = array_merge(
			self::turi_boyicha('assign', $year, $courseids),
			self::turi_boyicha('quiz', $year, $courseids)
		);
		// Muddat bo'yicha kamayish tartibida — yaqin ish yuqorida.
		usort($out, fn($a, $b) => ($b->duedate <=> $a->duedate)
			?: strcmp($a->guruh, $b->guruh));
		return $out;
	}

	/**
	 * Bitta faoliyat turi bo'yicha ro'yxat.
	 *
	 * NEGA IKKI TUR: odam «topshiriq» deganda Moodle'ning `assign` ini ham,
	 * `quiz` ini ham tushunadi va ularni ayirmaydi. Faqat `assign` ni
	 * ko'rsatganda sahifa «hech narsa yo'q» deb turardi, holbuki maktabda
	 * test (quiz) yaratilgan edi. Ikki jadval tuzilishi boshqa, shuning
	 * uchun so'rov ham alohida, lekin natija BIR XIL shaklda qaytadi.
	 *
	 * @param string $modul 'assign' yoki 'quiz'
	 */
	protected static function turi_boyicha(string $modul, ?string $year,
											?array $courseids): array {
		global $DB;

		$modid = $DB->get_field('modules', 'id', ['name' => $modul]);
		if (!$modid) {
			return [];   // modul o'chirilgan
		}

		$params = ['modid' => $modid];
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

		if ($modul === 'assign') {
			// `latest = 1` — bir o'quvchining bir necha urinishi bo'ladi.
			// `grade >= 0` — baholanmagan qator -1 bilan yoziladi.
			$qism = "a.duedate, a.allowsubmissionsfromdate AS ochilish,
					 a.submissiondrafts, a.markingworkflow,
					 (SELECT COUNT(DISTINCT s.userid) FROM {assign_submission} s
					   WHERE s.assignment = a.id AND s.latest = 1
						 AND s.status = 'submitted') AS topshirgan,
					 (SELECT COUNT(DISTINCT s.userid) FROM {assign_submission} s
					   WHERE s.assignment = a.id AND s.latest = 1
						 AND s.status = 'draft') AS qoralama,
					 (SELECT COUNT(DISTINCT g.userid) FROM {assign_grades} g
					   WHERE g.assignment = a.id AND g.grade IS NOT NULL
						 AND g.grade >= 0) AS baholangan,
					 (SELECT COUNT(DISTINCT uf.userid) FROM {assign_user_flags} uf
					   WHERE uf.assignment = a.id
						 AND uf.workflowstate = 'released') AS elon";
		} else {
			// Quiz'da «qoralama» = boshlangan, tugatilmagan urinish
			// (`inprogress`/`overdue`) — assign'dagi draft bilan bir xil ma'no.
			// Marking workflow quiz'da YO'Q, shuning uchun 0.
			$qism = "a.timeclose AS duedate, a.timeopen AS ochilish,
					 0 AS submissiondrafts, 0 AS markingworkflow,
					 (SELECT COUNT(DISTINCT qa.userid) FROM {quiz_attempts} qa
					   WHERE qa.quiz = a.id AND qa.preview = 0
						 AND qa.state = 'finished') AS topshirgan,
					 (SELECT COUNT(DISTINCT qa.userid) FROM {quiz_attempts} qa
					   WHERE qa.quiz = a.id AND qa.preview = 0
						 AND qa.state IN ('inprogress', 'overdue')) AS qoralama,
					 (SELECT COUNT(DISTINCT qg.userid) FROM {quiz_grades} qg
					   WHERE qg.quiz = a.id AND qg.grade IS NOT NULL) AS baholangan,
					 0 AS elon";
		}

		$sql = "SELECT cm.id AS cmid, a.id AS instanceid, a.name, a.grade AS bahoturi,
					   $qism,
					   cm.visible, c.id AS courseid, c.fullname AS coursename,
					   c.idnumber, c.showgrades, gi.hidden AS itemhidden,
					   l.id AS lessonid
				  FROM {" . $modul . "} a
				  JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = :modid
				  JOIN {course} c ON c.id = cm.course
			 LEFT JOIN {grade_items} gi ON gi.courseid = c.id
					   AND gi.itemtype = 'mod' AND gi.itemmodule = '" . $modul . "'
					   AND gi.iteminstance = a.id AND gi.itemnumber = 0
			 LEFT JOIN {local_targetzenit_lesson} l ON l.assigncmid = cm.id
				 WHERE $where";

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
				'turi' => $modul,
				'cmid' => (int) $r->cmid,
				'name' => (string) $r->name,
				'duedate' => (int) $r->duedate,
				'ochilish' => (int) $r->ochilish,
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
	 * @return array [holat, sabab_kaliti] — holat chip klassi:
	 *         good|warning|critical|none (`ui::chip()` konvensiyasi)
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
		return ['good', 'tp_vis_ok'];
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

	/** Faoliyatning o'z sahifasi — «ochish». */
	public static function ochish_url(\stdClass $r): \moodle_url {
		return new \moodle_url('/mod/' . $r->turi . '/view.php', ['id' => $r->cmid]);
	}

	/**
	 * Natijalar/baholash sahifasi — tur bo'yicha BOSHQA manzil.
	 *
	 * Moodle 4.3+ da assign'dagi «View all submissions» tugmasi olib
	 * tashlangan va tab'ga ko'chgan, quiz'da esa natijalar umuman boshqa
	 * sahifada (`report.php`). Shuning uchun to'g'ri manzilni shu yerda
	 * qotib yozamiz — odam tab qidirmasligi kerak.
	 */
	public static function baholash_url(\stdClass $r): \moodle_url {
		if ($r->turi === 'quiz') {
			return new \moodle_url('/mod/quiz/report.php',
				['id' => $r->cmid, 'mode' => 'overview']);
		}
		return new \moodle_url('/mod/assign/view.php',
			['id' => $r->cmid, 'action' => 'grading']);
	}

	/** Sozlamalar — muddat shu yerda o'zgaradi (ikki tur uchun bir xil). */
	public static function sozlama_url(int $cmid): \moodle_url {
		return new \moodle_url('/course/modedit.php', ['update' => $cmid, 'return' => 1]);
	}

	/**
	 * O'chirish — Moodle'ning TASDIQLASH sahifasiga olib boradi.
	 *
	 * Ataylab to'g'ridan-to'g'ri o'chirmaydi: `sesskey` bo'lsa ham Moodle
	 * avval «rostdan o'chirilsinmi?» deb so'raydi. Buzg'unchi amal bir
	 * bosishda bajarilmasligi kerak.
	 */
	public static function ochirish_url(int $cmid): \moodle_url {
		return new \moodle_url('/course/mod.php',
			['delete' => $cmid, 'sesskey' => sesskey()]);
	}
}
