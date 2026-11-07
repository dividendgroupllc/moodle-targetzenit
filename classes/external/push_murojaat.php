<?php
// Frappe -> Moodle: ota-ona murojaatlarini qabul qilish.
//
// FAQAT MANBA MAYDONLARI YOZILADI (matn, turi, kategoriya, yo'nalish,
// o'quvchi, menejer, vaqt). Yuritish maydonlari — holat, masul, qaror,
// ko'rildi — Moodle'ning O'ZIGA tegishli va bu yerda hech qachon
// tegilmaydi. Aks holda rahbarning qarori keyingi push'da yo'qolardi.
//
// BOG'LANISH: `studentidnumber` = Frappe `Student.name`,
// `manageridnumber` = Frappe `Employee.name`. Moodle foydalanuvchisi shu
// qiymat `user.idnumber` da bo'lgani bo'yicha topiladi — ism yoki email
// bo'yicha hech qachon qidirilmaydi.

namespace local_targetzenit\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_targetzenit\murojaat;

defined('MOODLE_INTERNAL') || die();

class push_murojaat extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'rows' => new external_multiple_structure(
                new external_single_structure([
                    'frappename' => new external_value(PARAM_TEXT, 'Frappe Murojaat.name'),
                    'turi' => new external_value(PARAM_TEXT, "Shikoyat|Taklif|Muammo|So'rov", VALUE_DEFAULT, 'Shikoyat'),
                    'kategoriya' => new external_value(PARAM_TEXT, 'Kategoriya nomi', VALUE_DEFAULT, ''),
                    'yonalish' => new external_value(PARAM_TEXT, 'CEO yoki Direktor', VALUE_DEFAULT, 'CEO'),
                    'muhimlik' => new external_value(PARAM_TEXT, "Past|O'rta|Yuqori", VALUE_DEFAULT, "O'rta"),
                    'matn' => new external_value(PARAM_TEXT, 'Ota-ona nima dedi'),
                    'studentidnumber' => new external_value(PARAM_TEXT, 'Frappe Student.name', VALUE_DEFAULT, ''),
                    'studentname' => new external_value(PARAM_TEXT, "O'quvchi ismi", VALUE_DEFAULT, ''),
                    'sinf' => new external_value(PARAM_TEXT, 'Sinf', VALUE_DEFAULT, ''),
                    'payername' => new external_value(PARAM_TEXT, 'Ota-ona / to\'lovchi', VALUE_DEFAULT, ''),
                    'payerphone' => new external_value(PARAM_TEXT, 'Telefon', VALUE_DEFAULT, ''),
                    'manageridnumber' => new external_value(PARAM_TEXT, 'Frappe Employee.name', VALUE_DEFAULT, ''),
                    'managername' => new external_value(PARAM_TEXT, 'Menejer ismi', VALUE_DEFAULT, ''),
                    'manba' => new external_value(PARAM_TEXT, 'Manba', VALUE_DEFAULT, ''),
                    'qarzsumma' => new external_value(PARAM_FLOAT, 'Ochiq qarz summasi', VALUE_DEFAULT, 0),
                    'qarzholat' => new external_value(PARAM_TEXT, 'Qarz ishining holati', VALUE_DEFAULT, ''),
                    'qarzkechikish' => new external_value(PARAM_TEXT, "Kechikish oralig'i", VALUE_DEFAULT, ''),
                    // SUHBATLAR JSON SATR BO'LIB KELADI, ichma-ich struktura
                    // emas. Sabab: Moodle REST so'rovni forma maydonlari
                    // ko'rinishida oladi — 120 murojaat x 10 suhbat x 6 maydon
                    // = 7200 maydon, PHP `max_input_vars` esa 5000. JSON bilan
                    // har murojaatga BITTA maydon ketadi.
                    'suhbatlar' => new external_value(PARAM_RAW,
                        "Aloqa tarixi JSON: [{vaqt, menejer, kanal, natija, suhbatnatija, izoh}]",
                        VALUE_DEFAULT, ''),
                    'suhbatvaqti' => new external_value(PARAM_INT, 'Suhbat vaqti (unix)', VALUE_DEFAULT, 0),
                    'ochilgan' => new external_value(PARAM_INT, 'Ochilgan vaqt (unix)', VALUE_DEFAULT, 0),
                    'slasana' => new external_value(PARAM_INT, 'Javob muddati (unix)', VALUE_DEFAULT, 0),
                ]),
                'Murojaat qatorlari',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    public static function execute(array $rows = []): array {
        global $DB;

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/targetzenit:push', $context);

        $params = self::validate_parameters(self::execute_parameters(), ['rows' => $rows]);
        $now = time();
        $yangi = $yangilandi = $otkazildi = 0;
        $akkauntsiz = [];

        foreach ($params['rows'] as $r) {
            $kalit = trim((string) $r['frappename']);
            if ($kalit === '' || trim((string) $r['matn']) === '') {
                $otkazildi++;
                continue;
            }
            $yonalish = isset(murojaat::CAPS[$r['yonalish']]) ? $r['yonalish'] : 'CEO';

            // Akkauntlar idnumber bo'yicha. Topilmasa 0 qoladi: qator
            // saqlanadi, panel esa "akkaunt topilmadi" deb ko'rsatadi —
            // jim yo'qotishdan ko'ra ko'rinib turgan nuqson yaxshi.
            $studentuserid = self::userid_by_idnumber((string) $r['studentidnumber']);
            $manageruserid = self::userid_by_idnumber((string) $r['manageridnumber']);
            if ($r['studentidnumber'] !== '' && !$studentuserid) {
                $akkauntsiz[] = $r['studentidnumber'];
            }

            $manba = [
                'turi' => (string) $r['turi'],
                'kategoriya' => (string) $r['kategoriya'],
                'yonalish' => $yonalish,
                'muhimlik' => (string) $r['muhimlik'],
                'matn' => \core_text::substr((string) $r['matn'], 0, murojaat::MAX),
                'studentuserid' => $studentuserid,
                'studentidnumber' => (string) $r['studentidnumber'],
                'studentname' => (string) $r['studentname'],
                'sinf' => (string) $r['sinf'],
                'payername' => (string) $r['payername'],
                'payerphone' => (string) $r['payerphone'],
                'manageruserid' => $manageruserid,
                'manageridnumber' => (string) $r['manageridnumber'],
                'managername' => (string) $r['managername'],
                'manba' => (string) $r['manba'],
                'qarzsumma' => (float) $r['qarzsumma'],
                'qarzholat' => (string) $r['qarzholat'],
                'qarzkechikish' => (string) $r['qarzkechikish'],
                'suhbatvaqti' => (int) $r['suhbatvaqti'],
                'ochilgan' => (int) $r['ochilgan'] ?: $now,
                'slasana' => (int) $r['slasana'],
            ];

            $bor = $DB->get_record('local_targetzenit_murojaat', ['frappename' => $kalit]);
            if ($bor) {
                // Yopilgan murojaatning manbasiga tegilmaydi: ota-ona
                // ilovada o'qigan matn keyin o'zgarib ketmasligi kerak.
                if ($bor->holat === 'Yopildi') {
                    $otkazildi++;
                    continue;
                }
                $yangilash = array_merge(['id' => $bor->id, 'timemodified' => $now], $manba);
                $DB->update_record('local_targetzenit_murojaat', (object) $yangilash);
                self::suhbatlar_yoz((int) $bor->id, (string) $r['suhbatlar']);
                $yangilandi++;
                continue;
            }

            $yozuv = array_merge($manba, [
                'frappename' => $kalit,
                'holat' => 'Yangi',
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
            $id = (int) $DB->insert_record('local_targetzenit_murojaat', (object) $yozuv);
            self::suhbatlar_yoz($id, (string) $r['suhbatlar']);
            murojaat::izoh_yoz($id, $manageruserid,
                get_string('mur_fromsales', 'local_targetzenit',
                    (string) ($r['manba'] ?: '—')), 'holat');
            $yangi++;
        }

        return [
            'yangi' => $yangi,
            'yangilandi' => $yangilandi,
            'otkazildi' => $otkazildi,
            'akkauntsiz' => array_values(array_unique($akkauntsiz)),
        ];
    }

    /**
     * Aloqa tarixini almashtiradi (o'chirib, qaytadan yozadi).
     *
     * NEGA ALMASHTIRISH: suhbatlar Frappe'da tug'iladi va u yerda
     * tahrirlanishi mumkin (izoh to'g'rilanadi, yozuv o'chiriladi). Qo'shib
     * borish usuli bilan Moodle'da eski nusxalar to'planib qolardi. Bo'sh
     * JSON kelsa TEGILMAYDI: push bo'laklab ketyapti degani ham bo'lishi
     * mumkin, mavjud tarixni bekorga o'chirib yubormaslik kerak.
     */
    protected static function suhbatlar_yoz(int $murojaatid, string $json): void {
        global $DB;

        $json = trim($json);
        if ($json === '') {
            return;
        }
        $qatorlar = json_decode($json, true);
        if (!is_array($qatorlar)) {
            return;
        }

        // O'ZGARMAGAN BO'LSA TEGMAYMIZ. Sinxron har 15 daqiqada ishlaydi va
        // suhbat tarixi odatda o'zgarmaydi; har safar o'chirib qayta yozish
        // PostgreSQL'da o'lik qatorlar to'plab, autovacuum'ni bekorga
        // yurgizadi (2026-11-07 tezlik tekshiruvi).
        if (self::suhbat_imzosi($qatorlar) === self::mavjud_imzo($murojaatid)) {
            return;
        }

        $DB->delete_records('local_targetzenit_murojaat_suhbat',
            ['murojaatid' => $murojaatid]);
        foreach ($qatorlar as $q) {
            if (!is_array($q)) {
                continue;
            }
            $DB->insert_record('local_targetzenit_murojaat_suhbat', (object) [
                'murojaatid' => $murojaatid,
                'vaqt' => (int) ($q['vaqt'] ?? 0),
                'menejer' => \core_text::substr((string) ($q['menejer'] ?? ''), 0, 255),
                'kanal' => \core_text::substr((string) ($q['kanal'] ?? ''), 0, 32),
                'natija' => \core_text::substr((string) ($q['natija'] ?? ''), 0, 64),
                'suhbatnatija' => \core_text::substr((string) ($q['suhbatnatija'] ?? ''), 0, 64),
                'izoh' => (string) ($q['izoh'] ?? ''),
            ]);
        }
    }

    /** Kelgan suhbatlar ro'yxatining imzosi (solishtirish uchun). */
    protected static function suhbat_imzosi(array $qatorlar): string {
        $sodda = [];
        foreach ($qatorlar as $q) {
            if (!is_array($q)) {
                continue;
            }
            $sodda[] = [
                (int) ($q['vaqt'] ?? 0),
                (string) ($q['menejer'] ?? ''),
                (string) ($q['kanal'] ?? ''),
                (string) ($q['natija'] ?? ''),
                (string) ($q['suhbatnatija'] ?? ''),
                (string) ($q['izoh'] ?? ''),
            ];
        }
        return md5(json_encode($sodda));
    }

    /** Bazadagi suhbatlarning o'sha formatdagi imzosi. */
    protected static function mavjud_imzo(int $murojaatid): string {
        global $DB;

        $rows = $DB->get_records('local_targetzenit_murojaat_suhbat',
            ['murojaatid' => $murojaatid], 'vaqt DESC');
        $qatorlar = [];
        foreach ($rows as $r) {
            $qatorlar[] = [
                'vaqt' => (int) $r->vaqt,
                'menejer' => (string) $r->menejer,
                'kanal' => (string) $r->kanal,
                'natija' => (string) $r->natija,
                'suhbatnatija' => (string) $r->suhbatnatija,
                'izoh' => (string) $r->izoh,
            ];
        }
        return self::suhbat_imzosi($qatorlar);
    }

    /** idnumber -> Moodle user.id. Bir nechta bo'lsa birinchisi (idnumber unique). */
    protected static function userid_by_idnumber(string $idnumber): int {
        global $DB;
        $idnumber = trim($idnumber);
        if ($idnumber === '') {
            return 0;
        }
        $id = $DB->get_field_select('user', 'id',
            'idnumber = :idn AND deleted = 0', ['idn' => $idnumber], IGNORE_MULTIPLE);
        return (int) ($id ?: 0);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'yangi' => new external_value(PARAM_INT, 'Yangi ochilgan'),
            'yangilandi' => new external_value(PARAM_INT, 'Yangilangan'),
            'otkazildi' => new external_value(PARAM_INT, "O'tkazilgan (yopiq yoki nuqsonli)"),
            'akkauntsiz' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Moodle akkaunti topilmagan Student.name'),
                'Bog\'lanmagan o\'quvchilar'
            ),
        ]);
    }
}
