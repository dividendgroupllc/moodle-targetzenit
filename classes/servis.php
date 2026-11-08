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

		if ($natija['qoshildi']) {
			// Token ruxsatlari keshlanadi — tozalamasa yangi funksiya darhol ishlamaydi
			\cache_helper::invalidate_by_definition('core', 'external_services');
		}
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
		return $out;
	}
}
