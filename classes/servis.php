<?php
// Copyright (c) 2026, Target Zenit
//
// FRAPPE WEB SERVICE FUNKSIYALARINI SERVISGA BIRIKTIRISH.
//
// MUAMMO: `db/services.php` da funksiya e'lon qilinishi — uni ishlatish
// mumkin degani EMAS. Moodle tokenni SERVIS bo'yicha tekshiradi: funksiya
// o'sha servis ro'yxatida bo'lmasa, Frappe «accessexception: Access control
// exception» oladi. Shu paytgacha har yangi funksiya qo'lda, admin UI'dan
// qo'shilardi (Server -> Web services -> External services -> Functions) va
// har safar esdan chiqib, deploy «ishladi» bo'lsa ham push yiqilardi.
//
// YECHIM: funksiyalar ro'yxati kodda — shu klass `db/services.php` dagi
// `local_targetzenit_*` funksiyalarining HAMMASINI Frappe servisiga biriktirib
// qo'yadi. `db/upgrade.php` har deployda shuni chaqiradi.
//
// SERVIS QANDAY TOPILADI: nomi bo'yicha emas (u o'zgarishi mumkin), balki
// allaqachon ishlayotgan `local_targetzenit_push_timetable` funksiyasi qaysi
// servisda bo'lsa — o'sha. Ya'ni tokeni bor, jadvali ketayotgan servis.
// Topilmasa — hech narsa qilinmaydi (yangi servis YARATILMAYDI: token
// yaratish va ruxsat berish admin ishi, kod o'zboshimchalik qilmaydi).

namespace local_targetzenit;

defined('MOODLE_INTERNAL') || die();

class servis {

	/** Servisni aniqlash uchun tayanch funksiya — eng eski, doim bor. */
	const TAYANCH = 'local_targetzenit_push_timetable';

	/**
	 * Frappe'ga kerak MOODLE YADRO funksiyalari.
	 *
	 * Plagin funksiyalari `db/services.php` dan o'qiladi, yadro funksiyalari
	 * esa (kurs yaratish/yangilash, yozilish) shu paytgacha admin UI'dan
	 * QO'LDA biriktirilgan — va yangi kerak bo'lgani har safar esdan chiqib,
	 * Frappe `accessexception` olardi. Shuning uchun ro'yxat kodda turadi.
	 *
	 * DIQQAT: bu ro'yxat Frappe tokeniga beriladigan ruxsat — faqat
	 * integratsiyaga HAQIQATAN kerak bo'lganini yozamiz.
	 *
	 * - core_course_delete_courses — kursni Frappe'dan o'chirish
	 *   (`Moodle Course Link` yozuvi o'chirilsa yoki «Amal = O'chirish»).
	 * - core_enrol_get_enrolled_users — tekshiruv uchun: Moodle'dagi HAQIQIY
	 *   yozilishni o'qib, Frappe jadvali bilan solishtirish (qo'lda qilingan
	 *   o'zgarishlarni topish). Faqat o'qiydi.
	 */
	const YADRO = [
		'core_course_delete_courses',
		'core_enrol_get_enrolled_users',
	];

	/**
	 * Plagin funksiyalarini Frappe servisiga biriktiradi.
	 *
	 * @param bool $chiqar CLI uchun: nima bo'layotganini bosib chiqarish
	 * @return array ['servis' => shortname|null, 'qoshildi' => [...], 'bor' => int]
	 */
	public static function sinxronla(bool $chiqar = false): array {
		global $CFG, $DB;

		$natija = ['servis' => null, 'qoshildi' => [], 'bor' => 0];

		// TARTIB MUAMMOSI: Moodle `db/upgrade.php` ni `external_functions`
		// jadvalini yangilashdan OLDIN chaqiradi (`upgrade_plugins()` ->
		// `xmldb_..._upgrade()`, keyin `upgrade_component_updated()` ->
		// `external_update_descriptions()`). Ya'ni upgrade ichida yangi
		// funksiya hali ro'yxatda bo'lmaydi va biz uni servisga biriktira
		// olmaymiz. Shuning uchun ro'yxatni O'ZIMIZ yangilaymiz — bu core
		// funksiyasi va ikki marta chaqirilsa ham xavfsiz (idempotent).
		require_once($CFG->libdir . '/upgradelib.php');
		external_update_descriptions('local_targetzenit');

		$servisid = $DB->get_field('external_services_functions', 'externalserviceid',
			['functionname' => self::TAYANCH], IGNORE_MULTIPLE);
		if (!$servisid) {
			if ($chiqar) {
				mtrace("  Frappe servisi topilmadi («" . self::TAYANCH . "» hech qaysi "
					. "servisda yo'q) — funksiyalar biriktirilmadi.");
				mtrace("  Admin UI'dan servis yarating va shu funksiyani qo'shing, "
					. "keyin bu skriptni qayta ishga tushiring.");
			}
			return $natija;
		}

		$servis = $DB->get_record('external_services', ['id' => $servisid]);
		$natija['servis'] = $servis ? $servis->shortname : (string)$servisid;

		// Shu servisda allaqachon bor funksiyalar
		$bor = $DB->get_fieldset_select('external_services_functions', 'functionname',
			'externalserviceid = ?', [$servisid]);
		$bor = array_flip($bor);
		$natija['bor'] = count($bor);

		foreach (self::funksiyalar() as $nom) {
			if (isset($bor[$nom])) {
				continue;
			}
			// Funksiya Moodle'da ro'yxatdan o'tganmi (plagin yangilangandan keyin)
			if (!$DB->record_exists('external_functions', ['name' => $nom])) {
				if ($chiqar) {
					mtrace("  O'TKAZILDI: $nom — Moodle ro'yxatida yo'q "
						. "(plagin versiyasini oshirib, upgrade qiling)");
				}
				continue;
			}
			$DB->insert_record('external_services_functions', (object)[
				'externalserviceid' => $servisid,
				'functionname'      => $nom,
			]);
			$natija['qoshildi'][] = $nom;
			if ($chiqar) {
				mtrace("  QO'SHILDI: $nom");
			}
		}

		// KESH TOZALASH SHART EMAS — va «core/external_services» degan kesh
		// ta'rifi Moodle'da UMUMAN YO'Q (`lib/db/caches.php` da bunday nom
		// yo'q). O'sha chaqiruv `codingerror: The requested cache definition
		// does not exist` berib BUTUN MIGRATSIYANI yiqitgan (2026-11-08
		// deploy, 75fa4d9).
		//
		// Nega shart emas: Moodle yadrosining o'zi
		// `webservice::add_external_function_to_service()` da faqat
		// `insert_record` qiladi va hech narsa tozalamaydi — token ruxsati
		// keshda emas, har so'rovda jonli SQL bilan tekshiriladi
		// (`webservice/lib.php` dagi `external_services_functions` JOIN i).
		return $natija;
	}

	/**
	 * Servisga biriktirilishi kerak funksiyalar — `db/services.php` dan o'qiladi.
	 *
	 * Mobil ilova funksiyalari CHETLAB O'TILADI: ular `services` kalitida
	 * `moodle_mobile_app` ga avtomatik biriktiriladi, Frappe servisiga esa
	 * kirmasligi kerak (Frappe tokeniga ortiqcha ruxsat bermaymiz).
	 */
	public static function funksiyalar(): array {
		$functions = [];
		require(__DIR__ . '/../db/services.php');

		$out = [];
		foreach ($functions as $nom => $tavsif) {
			if (!empty($tavsif['services'])) {
				continue;   // o'z servisi bor (mobil ilova)
			}
			if (!empty($tavsif['ajax'])) {
				continue;   // brauzerdan chaqiriladigan — Frappe uchun emas
			}
			$out[] = $nom;
		}
		foreach (self::YADRO as $nom) {
			$out[] = $nom;
		}
		return $out;
	}
}
